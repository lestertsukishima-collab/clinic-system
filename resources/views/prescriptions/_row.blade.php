@php($isPatient = auth()->user()->role === 'patient')
<tr>
    <td>
        <div class="clinic-person-cell">
            <span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
            <span class="clinic-person-name">{{ $prescription->appointment->patient->name }}</span>
        </div>
    </td>
    <td>{{ $prescription->appointment->doctor->display_name }}</td>
    <td>
        <div class="clinic-date-cell">
            <span>{{ $prescription->appointment->local_appointment_date->format('M d, Y') }}</span>
            <small>{{ $prescription->appointment->local_appointment_date->format('g:i A') }}</small>
        </div>
    </td>
    <td><span class="clinic-diagnosis-preview">{{ \Illuminate\Support\Str::limit($prescription->diagnosis, 68) }}</span></td>
    <td>
        <div class="clinic-row-actions">
            <a href="{{ route($isPatient ? 'patient-prescriptions.show' : 'prescriptions.show', $prescription) }}" class="clinic-action-button clinic-action-view">
                <i class="bi bi-eye" aria-hidden="true"></i> View
            </a>
        </div>
    </td>
</tr>
