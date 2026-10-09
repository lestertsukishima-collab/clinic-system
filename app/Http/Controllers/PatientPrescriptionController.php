<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientPrescriptionController extends Controller
{
    public function index(Request $request): View
    {
        return view('prescriptions.index', [
            'prescriptions' => $this->accessiblePrescriptions($request)
                ->latest()->orderByDesc('id')->paginate(10),
        ]);
    }

    public function show(Request $request, Prescription $prescription): View
    {
        return view('prescriptions.show', [
            'prescription' => $this->accessiblePrescriptions($request)->findOrFail($prescription->id),
        ]);
    }

    public function print(Request $request, Prescription $prescription): View
    {
        return view('print', [
            'prescription' => $this->accessiblePrescriptions($request)->findOrFail($prescription->id),
        ]);
    }

    private function accessiblePrescriptions(Request $request): Builder
    {
        return Prescription::query()
            ->whereHas('appointment', fn (Builder $query) => $query
                ->where('patient_id', $request->user()->id)
                ->where('status', 'completed'))
            ->with(['appointment.patient', 'appointment.doctor.user', 'appointment.service']);
    }
}
