<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorUnavailability;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, ['admin', 'doctor', 'patient'], true), 403);

        if ($user->role === 'admin') {
            return $this->adminDashboard()->with($this->availabilityNotices($user));
        }

        if ($user->role === 'doctor') {
            $validated = $request->validate([
                'schedule' => ['nullable', 'in:all,today'],
            ]);

            return $this->doctorDashboard($user, $validated['schedule'] ?? 'all')->with($this->availabilityNotices($user));
        }

        return $this->patientDashboard($user)->with($this->availabilityNotices($user));
    }

    /**
     * @return array{unavailabilities: \Illuminate\Database\Eloquent\Collection<int, DoctorUnavailability>, affectedAppointments: Collection<int, Appointment>}
     */
    private function availabilityNotices(User $user): array
    {
        $unavailabilities = DoctorUnavailability::query()->with('doctor.user')
            ->where('ends_at', '>', now())
            ->when($user->role === 'doctor', fn (Builder $query) => $query
                ->whereHas('doctor', fn (Builder $doctor) => $doctor->where('user_id', $user->id)))
            ->orderBy('starts_at')->orderBy('id')->get();
        $affectedAppointments = collect();

        if ($unavailabilities->isNotEmpty()) {
            $affectedAppointments = Appointment::query()->with(['doctor.user', 'patient'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('appointment_date', '>', now()->subMinutes(config('clinic.appointment_duration_minutes')))
                ->when($user->role === 'patient', fn (Builder $query) => $query->where('patient_id', $user->id))
                ->where(function (Builder $query) use ($unavailabilities): void {
                    foreach ($unavailabilities as $unavailability) {
                        $query->orWhere(fn (Builder $booking) => $booking->where('doctor_id', $unavailability->doctor_id)
                            ->where('appointment_date', '>', $unavailability->starts_at->subMinutes(config('clinic.appointment_duration_minutes')))
                            ->where('appointment_date', '<', $unavailability->ends_at));
                    }
                })->orderBy('appointment_date')->orderBy('id')->get();
        }

        return compact('unavailabilities', 'affectedAppointments');
    }

    private function patientDashboard(User $user): View
    {
        $appointments = Appointment::query()->where('patient_id', $user->id);

        return view('dashboard', [
            'appointments' => (clone $appointments)->with(['doctor.user', 'service'])
                ->latest('appointment_date')->orderByDesc('id')->paginate(10),
            'pendingCount' => (clone $appointments)->where('status', 'pending')->count(),
            'completedCount' => (clone $appointments)->where('status', 'completed')->count(),
            'prescriptionCount' => $user->patientPrescriptions()->count(),
            'nextAppointment' => (clone $appointments)->with(['doctor.user', 'service'])
                ->where('status', 'confirmed')->where('appointment_date', '>=', now())
                ->orderBy('appointment_date')->orderBy('id')->first(),
        ]);
    }

    private function doctorDashboard(User $user, string $schedule): View
    {
        $clinicToday = now(config('clinic.timezone'))->startOfDay();
        $appointments = Appointment::query()
            ->whereHas('doctor', fn (Builder $query) => $query->where('user_id', $user->id));
        $todayAppointments = (clone $appointments)
            ->where('appointment_date', '>=', $clinicToday->copy()->utc())
            ->where('appointment_date', '<', $clinicToday->copy()->addDay()->utc());
        $pendingAppointments = (clone $appointments)->where('status', 'pending');
        $scheduleAppointments = $schedule === 'today' ? clone $todayAppointments : clone $appointments;

        return view('doctor-dashboard', [
            'doctor' => $user->doctor,
            'clinicToday' => $clinicToday,
            'schedule' => $schedule,
            'appointmentCount' => (clone $appointments)->count(),
            'todayCount' => (clone $todayAppointments)->count(),
            'completedTodayCount' => (clone $todayAppointments)->where('status', 'completed')->count(),
            'pendingCount' => (clone $pendingAppointments)->count(),
            'prescriptionCount' => Prescription::query()
                ->whereHas('appointment.doctor', fn (Builder $query) => $query->where('user_id', $user->id))
                ->count(),
            'scheduleAppointments' => $scheduleAppointments->with(['patient', 'service'])
                ->orderBy('appointment_date', $schedule === 'today' ? 'asc' : 'desc')
                ->orderBy('id')->paginate(10)->appends(['schedule' => $schedule]),
            'pendingAppointments' => $pendingAppointments->with(['patient', 'service'])
                ->orderBy('appointment_date')->orderBy('id')->limit(5)->get(),
            'nextAppointment' => (clone $appointments)->with(['patient', 'service'])
                ->where('status', 'confirmed')->where('appointment_date', '>=', now())
                ->orderBy('appointment_date')->orderBy('id')->first(),
        ]);
    }

    private function adminDashboard(): View
    {
        $clinicToday = now(config('clinic.timezone'))->startOfDay();
        $todayQuery = Appointment::query()
            ->where('appointment_date', '>=', $clinicToday->copy()->utc())
            ->where('appointment_date', '<', $clinicToday->copy()->addDay()->utc());
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $requestsThisMonthQuery = Appointment::query()->whereBetween('created_at', [$monthStart, $monthEnd]);
        $requestsThisMonth = (clone $requestsThisMonthQuery)->count();
        $patientsRequestingThisMonth = (clone $requestsThisMonthQuery)->distinct()->count('patient_id');
        $monthlyRequests = collect();

        foreach (range(5, 0) as $monthsAgo) {
            $chartMonthStart = now()->startOfMonth()->subMonths($monthsAgo);
            $chartMonthEnd = $chartMonthStart->copy()->endOfMonth();

            $monthlyRequests->push([
                'label' => $chartMonthStart->format('M'),
                'count' => Appointment::query()
                    ->whereBetween('created_at', [$chartMonthStart, $chartMonthEnd])
                    ->count(),
            ]);
        }

        return view('admin-dashboard', [
            'clinicToday' => $clinicToday,
            'todayAppointmentCount' => (clone $todayQuery)->count(),
            'todayConfirmedCount' => (clone $todayQuery)->where('status', 'confirmed')->count(),
            'todayCompletedCount' => (clone $todayQuery)->where('status', 'completed')->count(),
            'todayAppointments' => (clone $todayQuery)
                ->whereIn('status', ['pending', 'confirmed'])
                ->with(['patient', 'doctor.user', 'service'])
                ->orderBy('appointment_date')
                ->orderBy('id')
                ->limit(5)
                ->get(),
            'requestsThisMonth' => $requestsThisMonth,
            'patientsRequestingThisMonth' => $patientsRequestingThisMonth,
            'pendingRequests' => Appointment::query()->where('status', 'pending')->count(),
            'registeredPatients' => User::query()->where('role', 'patient')->count(),
            'doctorCount' => Doctor::query()->count(),
            'monthlyRequests' => $monthlyRequests,
            'maxMonthlyRequests' => max(1, (int) $monthlyRequests->max('count')),
            'recentAppointments' => Appointment::query()
                ->with(['patient', 'doctor.user', 'service'])
                ->latest('created_at')
                ->orderByDesc('id')
                ->paginate(10),
        ]);
    }
}
