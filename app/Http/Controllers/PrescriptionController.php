<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $prescriptions = $this->accessiblePrescriptions($user)
            ->with(['appointment.patient', 'appointment.doctor.user', 'appointment.service'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        return view('prescriptions.index', compact('prescriptions'));
    }

    public function create(Request $request): View
    {
        $user = $this->authenticatedUser($request);

        return view('prescriptions.create', [
            'prescription' => new Prescription,
            'appointments' => $this->accessibleAppointments($user)->latest('appointment_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $validated = $this->validatedPrescription($request);
        $appointment = $this->accessibleAppointments($user)->findOrFail($validated['appointment_id']);

        $prescription = $appointment->prescriptions()->create([
            'diagnosis' => $validated['diagnosis'],
            'medicines' => $validated['medicines'],
            'instructions' => $validated['instructions'] ?? null,
        ]);

        return redirect()->route('prescriptions.show', $prescription)->with('success', 'Prescription created successfully.');
    }

    public function show(Request $request, Prescription $prescription): View
    {
        $prescription = $this->findAccessiblePrescription(
            $this->authenticatedUser($request),
            $prescription->id
        );

        return view('prescriptions.show', [
            'prescription' => $prescription->load(['appointment.patient', 'appointment.doctor.user', 'appointment.service']),
        ]);
    }

    public function edit(Request $request, Prescription $prescription): View
    {
        $user = $this->authenticatedUser($request);
        $prescription = $this->findAccessiblePrescription($user, $prescription->id);

        return view('prescriptions.edit', [
            'prescription' => $prescription->load('appointment'),
            'appointments' => $this->accessibleAppointments($user)->latest('appointment_date')->get(),
        ]);
    }

    public function update(Request $request, Prescription $prescription): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $prescription = $this->findAccessiblePrescription($user, $prescription->id);
        $validated = $this->validatedPrescription($request);
        $appointment = $this->accessibleAppointments($user)->findOrFail($validated['appointment_id']);

        $prescription->update([
            'appointment_id' => $appointment->id,
            'diagnosis' => $validated['diagnosis'],
            'medicines' => $validated['medicines'],
            'instructions' => $validated['instructions'] ?? null,
        ]);

        return redirect()->route('prescriptions.show', $prescription)->with('success', 'Prescription updated successfully.');
    }

    public function destroy(Request $request, Prescription $prescription): RedirectResponse
    {
        $prescription = $this->findAccessiblePrescription(
            $this->authenticatedUser($request),
            $prescription->id
        );
        $prescription->delete();

        return redirect()->route('prescriptions.index')->with('success', 'Prescription deleted successfully.');
    }

    public function print(Request $request, Prescription $prescription): View
    {
        $prescription = $this->findAccessiblePrescription(
            $this->authenticatedUser($request),
            $prescription->id
        );

        return view('print', [
            'prescription' => $prescription->load(['appointment.patient', 'appointment.doctor.user']),
        ]);
    }

    /**
     * @return array{appointment_id: int, diagnosis: string, medicines: string, instructions?: string|null}
     */
    private function validatedPrescription(Request $request): array
    {
        return $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'diagnosis' => ['required', 'string', 'max:5000'],
            'medicines' => ['required', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, ['admin', 'doctor'], true), 403);

        return $user;
    }

    private function accessibleAppointments(User $user): Builder
    {
        $appointments = Appointment::query()->with(['patient', 'doctor.user', 'service']);

        if ($user->role === 'doctor') {
            $appointments->whereHas('doctor', fn (Builder $query) => $query->where('user_id', $user->id));
        }

        return $appointments;
    }

    private function accessiblePrescriptions(User $user): Builder
    {
        $prescriptions = Prescription::query();

        if ($user->role === 'doctor') {
            $prescriptions->whereHas('appointment.doctor', fn (Builder $query) => $query->where('user_id', $user->id));
        }

        return $prescriptions;
    }

    private function findAccessiblePrescription(User $user, int $id): Prescription
    {
        return $this->accessiblePrescriptions($user)->findOrFail($id);
    }
}
