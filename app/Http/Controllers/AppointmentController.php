<?php

namespace App\Http\Controllers;

use App\AppointmentWorkflow;
use App\Http\Requests\AppointmentRequest;
use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,confirmed,completed,cancelled'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'patient_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = $this->accessibleAppointments($user);

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['date'] ?? null) {
            $dayStart = Carbon::parse($filters['date'], config('clinic.timezone'))->startOfDay();
            $query->where('appointment_date', '>=', $dayStart->copy()->utc())
                ->where('appointment_date', '<', $dayStart->copy()->addDay()->utc());
        }

        $selectedPatient = null;

        if ($user->role === 'admin' && ($filters['patient_id'] ?? null)) {
            $selectedPatient = User::where('role', 'patient')->findOrFail($filters['patient_id']);
            $query->where('patient_id', $selectedPatient->id);
        }

        $appointments = $query
            ->orderBy('appointment_date', ($filters['date'] ?? null) ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('appointments.index', compact('appointments', 'filters', 'selectedPatient'));
    }

    public function create(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        abort_unless(in_array($user->role, ['admin', 'patient'], true), 403);

        return view('appointments.create', [
            'doctors' => Doctor::with('user')->orderBy('id')->get(),
            'services' => Service::orderBy('name')->get(),
            'patients' => $user->role === 'admin'
                ? User::where('role', 'patient')->orderBy('name')->get()
                : collect(),
            'appointment' => new Appointment,
        ]);
    }

    public function store(AppointmentRequest $request, AppointmentWorkflow $workflow): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $appointment = $workflow->schedule($user, $request->validated());

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment successfully requested.');
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $user = $this->authenticatedUser($request);
        $appointment = $this->findAccessibleAppointment($user, $appointment->id);

        if (in_array($user->role, ['admin', 'doctor'], true) || $appointment->status === 'completed') {
            $appointment->load('prescriptions');
        }

        return view('appointments.show', [
            'appointment' => $appointment,
        ]);
    }

    public function edit(Request $request, Appointment $appointment): View
    {
        $user = $this->authenticatedUser($request);
        $appointment = $this->findEditableAppointment($user, $appointment->id);

        return view('appointments.edit', [
            'appointment' => $appointment,
            'doctors' => Doctor::with('user')->orderBy('id')->get(),
            'services' => Service::orderBy('name')->get(),
            'patients' => $user->role === 'admin'
                ? User::where('role', 'patient')->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment, AppointmentWorkflow $workflow): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $appointment = $this->findEditableAppointment($user, $appointment->id);
        $appointment = $workflow->schedule($user, $request->validated(), $appointment);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment updated successfully.');
    }

    public function destroy(Request $request, Appointment $appointment, AppointmentWorkflow $workflow): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $appointment = $this->findAccessibleAppointment($user, $appointment->id);

        abort_unless($user->role === 'admin' || ($user->role === 'patient' && $appointment->status === 'pending'), 403);

        $workflow->cancel($user, $appointment);

        return redirect()->route('appointments.index')->with('success', 'Appointment cancelled.');
    }

    public function confirmAppointment(Request $request, Appointment $appointment, AppointmentWorkflow $workflow): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $validated = $request->validate([
            'appointment_date' => ['sometimes', 'required', 'string', 'date_format:Y-m-d\TH:i'],
        ]);
        $appointment = $workflow->confirm($user, $appointment, $validated['appointment_date'] ?? null);

        try {
            Mail::to($appointment->patient->email)->send(new AppointmentConfirmed($appointment));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Appointment confirmed, but the email could not be sent. Please notify the patient directly.',
            ]);
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Appointment confirmed and email sent.',
        ]);
    }

    public function completeAppointment(Request $request, Appointment $appointment, AppointmentWorkflow $workflow): RedirectResponse
    {
        $appointment = $workflow->complete($this->authenticatedUser($request), $appointment);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Visit marked as completed.');
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, ['admin', 'doctor', 'patient'], true), 403);

        return $user;
    }

    private function accessibleAppointments(User $user): Builder
    {
        $appointments = Appointment::query()->with(['patient', 'doctor.user', 'service']);

        if ($user->role === 'patient') {
            $appointments->where('patient_id', $user->id);
        } elseif ($user->role === 'doctor') {
            $appointments->whereHas('doctor', fn (Builder $query) => $query->where('user_id', $user->id));
        }

        return $appointments;
    }

    private function findAccessibleAppointment(User $user, int $id): Appointment
    {
        return $this->accessibleAppointments($user)->findOrFail($id);
    }

    private function findEditableAppointment(User $user, int $id): Appointment
    {
        $appointment = $this->findAccessibleAppointment($user, $id);
        abort_unless($user->role === 'admin' || ($user->role === 'patient' && $appointment->status === 'pending'), 403);
        abort_unless(in_array($appointment->status, ['pending', 'confirmed'], true), 409);

        return $appointment;
    }
}
