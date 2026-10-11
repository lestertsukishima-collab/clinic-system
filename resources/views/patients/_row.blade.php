<tr>
    <td><div class="clinic-person-cell"><span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span><span class="clinic-person-name">{{ $patient->name }}</span></div></td>
    <td>{{ $patient->email }}</td>
    <td>{{ $patient->created_at->timezone(config('clinic.timezone'))->format('M d, Y') }}</td>
    <td>{{ $patient->appointments_count }}</td>
    <td><div class="clinic-row-actions"><a href="{{ route('appointments.index', ['patient_id' => $patient->id]) }}" class="clinic-action-button clinic-action-view" aria-label="View appointments for {{ $patient->name }}">Appointments <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></td>
</tr>
