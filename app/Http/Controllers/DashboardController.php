<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, ['admin', 'doctor', 'patient'], true), 403);

        if ($user->role === 'admin') {
            return $this->adminDashboard();
        }

        $appointments = Appointment::query()->with(['patient', 'doctor.user', 'service']);

        if ($user->role === 'patient') {
            $appointments->where('patient_id', $user->id);
        } else {
            $appointments->whereHas('doctor', fn (Builder $query) => $query->where('user_id', $user->id));
        }

        return view('dashboard', [
            'appointments' => $appointments
                ->latest('appointment_date')
                ->orderByDesc('id')
                ->paginate(10),
        ]);
    }

    private function adminDashboard(): View
    {
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
