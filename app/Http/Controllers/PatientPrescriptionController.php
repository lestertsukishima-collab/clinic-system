<?php

namespace App\Http\Controllers;

use App\DataTable;
use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientPrescriptionController extends Controller
{
    public function index(Request $request, DataTable $table): View|JsonResponse
    {
        $query = $this->accessiblePrescriptions($request)->latest()->orderByDesc('id');

        if ($request->has('draw')) {
            return $table->response($request, $query, [
                Appointment::select('users.name')->join('users', 'users.id', '=', 'appointments.patient_id')
                    ->whereColumn('appointments.id', 'prescriptions.appointment_id'),
                Appointment::select('users.name')->join('doctors', 'doctors.id', '=', 'appointments.doctor_id')
                    ->join('users', 'users.id', '=', 'doctors.user_id')->whereColumn('appointments.id', 'prescriptions.appointment_id'),
                Appointment::select('appointment_date')->whereColumn('appointments.id', 'prescriptions.appointment_id'),
                'diagnosis',
            ], ['appointment.patient.name', 'appointment.doctor.user.name', 'diagnosis'], 'prescriptions._row', 'prescription');
        }

        return view('prescriptions.index', [
            'prescriptions' => $query->paginate(10),
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
                ->where('patient_id', $request->user()->id))
            ->with(['appointment.patient', 'appointment.doctor.user', 'appointment.service']);
    }
}
