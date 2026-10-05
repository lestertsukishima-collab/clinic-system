<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $appointments = $this->accessibleAppointments($this->authenticatedUser($request))
            ->latest('appointment_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('appointments.index', compact('appointments'));
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

    public function store(Request $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        abort_unless(in_array($user->role, ['admin', 'patient'], true), 403);

        $rules = [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'appointment_date' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($user->role === 'admin') {
            $rules['patient_id'] = ['required', 'integer', Rule::exists('users', 'id')->where('role', 'patient')];
        }

        $validated = $request->validate($rules);

        $appointment = Appointment::create([
            'patient_id' => $user->role === 'admin' ? $validated['patient_id'] : $user->id,
            'doctor_id' => $validated['doctor_id'],
            'service_id' => $validated['service_id'],
            'appointment_date' => $validated['appointment_date'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment successfully requested.');
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $appointment = $this->findAccessibleAppointment($this->authenticatedUser($request), $appointment->id);

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

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $appointment = $this->findEditableAppointment($user, $appointment->id);
        $validated = $request->validate([
            'patient_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->where('role', 'patient')],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'appointment_date' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $attributes = [
            'doctor_id' => $validated['doctor_id'],
            'service_id' => $validated['service_id'],
            'appointment_date' => $validated['appointment_date'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($user->role === 'admin' && array_key_exists('patient_id', $validated)) {
            $attributes['patient_id'] = $validated['patient_id'];
        }

        $appointment->update($attributes);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment updated successfully.');
    }

    public function destroy(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $appointment = $this->findAccessibleAppointment($user, $appointment->id);

        abort_unless($user->role === 'admin' || ($user->role === 'patient' && $appointment->status === 'pending'), 403);

        $appointment->update(['status' => 'cancelled']);

        return redirect()->route('appointments.index')->with('success', 'Appointment cancelled.');
    }

    public function confirmAppointment(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        abort_unless($user->role === 'admin' || $this->isAssignedDoctor($user, $appointment), 403);
        abort_unless($appointment->status === 'pending', 409);

        $appointment->load('patient');
        $appointment->update(['status' => 'confirmed']);

        Mail::to($appointment->patient->email)->send(new AppointmentConfirmed($appointment));

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Appointment confirmed and email sent.',
        ]);
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

        return $appointment;
    }

    private function isAssignedDoctor(User $user, Appointment $appointment): bool
    {
        return $user->role === 'doctor' && Doctor::whereKey($appointment->doctor_id)->where('user_id', $user->id)->exists();
    }
}
