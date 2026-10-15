<?php

namespace App\Http\Controllers;

use App\AppointmentWorkflow;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorUnavailability;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DoctorAvailabilityController extends Controller
{
    public function show(Request $request, Doctor $doctor, AppointmentWorkflow $workflow): JsonResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'string', 'date'],
            'appointment_id' => ['nullable', 'integer'],
        ]);
        $appointmentId = $validated['appointment_id'] ?? null;

        if ($appointmentId !== null) {
            $appointment = Appointment::query()->findOrFail($appointmentId);
            abort_unless($request->user()->role === 'admin'
                || ($request->user()->role === 'patient' && $appointment->patient_id === $request->user()->id)
                || ($request->user()->role === 'doctor' && $appointment->doctor->user_id === $request->user()->id), 404);
        }

        $date = CarbonImmutable::parse($validated['appointment_date'], config('clinic.timezone'))->utc();
        $message = $date->isFuture()
            ? $workflow->doctorAvailabilityMessage($doctor->id, $date, $appointmentId)
            : 'The appointment date must be a date after now.';

        return response()->json(['available' => $message === null, 'message' => $message]);
    }

    public function store(Request $request, Doctor $doctor, AppointmentWorkflow $workflow): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin' || $doctor->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);
        $workflow->markDoctorUnavailable(
            $request->user(), $doctor,
            CarbonImmutable::parse($validated['starts_at'], config('clinic.timezone')),
            CarbonImmutable::parse($validated['ends_at'], config('clinic.timezone')),
        );

        return back()->with('success', 'Doctor unavailability saved. Patients will see a notice on their dashboard.');
    }

    public function destroy(Request $request, DoctorUnavailability $unavailability, AppointmentWorkflow $workflow): RedirectResponse
    {
        $workflow->removeDoctorUnavailability($request->user(), $unavailability);

        return back()->with('success', 'Doctor unavailability removed.');
    }
}
