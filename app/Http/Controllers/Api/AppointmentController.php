<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $appointments = $this->accessibleAppointments($user)
            ->latest('appointment_date')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $appointments,
        ]);
    }

    public function store(Request $request): JsonResponse
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

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment successfully created.',
            'data' => $appointment->load(['patient:id,name', 'doctor.user:id,name', 'service:id,name,price']),
        ], 201);
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        abort_unless($this->accessibleAppointments($user)->whereKey($appointment->id)->exists(), 404);

        return response()->json([
            'status' => 'success',
            'data' => $appointment->load(['patient', 'doctor.user', 'service']),
        ]);
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        abort_unless(
            $user->role === 'admin'
                || ($user->role === 'patient'
                    && $appointment->patient_id === $user->id
                    && $appointment->status === 'pending'),
            404
        );

        $rules = [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'appointment_date' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($user->role === 'admin') {
            $rules['patient_id'] = ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->where('role', 'patient')];
        }

        $validated = $request->validate($rules);
        $appointment->update([
            'patient_id' => $user->role === 'admin' ? ($validated['patient_id'] ?? $appointment->patient_id) : $user->id,
            'doctor_id' => $validated['doctor_id'],
            'service_id' => $validated['service_id'],
            'appointment_date' => $validated['appointment_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment updated successfully.',
            'data' => $appointment->load(['patient:id,name', 'doctor.user:id,name', 'service:id,name,price']),
        ]);
    }

    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        abort_unless(
            $user->role === 'admin'
                || ($user->role === 'patient'
                    && $appointment->patient_id === $user->id
                    && $appointment->status === 'pending'),
            404
        );

        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment cancelled successfully.',
            'data' => $appointment->only(['id', 'status']),
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
}
