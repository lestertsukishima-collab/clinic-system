<tr>
    <td>
        <div class="clinic-person-cell">
            <span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
            <span class="clinic-person-name">{{ $appointment->patient->name }}</span>
        </div>
    </td>
    <td>{{ $appointment->doctor->display_name }}</td>
    <td class="clinic-service-cell">{{ $appointment->service->name }}</td>
    <td>
        <div class="clinic-date-cell">
            <span>{{ $appointment->local_requested_at->format('M d, Y') }}</span>
            <small>{{ $appointment->local_requested_at->format('g:i:s A') }}</small>
        </div>
    </td>
    <td>
        <div class="clinic-date-cell">
            <span>{{ $appointment->local_appointment_date->format('M d, Y') }}</span>
            <small>{{ $appointment->local_appointment_date->format('g:i A') }}</small>
        </div>
    </td>
    <td>
        <span class="clinic-status clinic-status-{{ strtolower($appointment->status) }}">
            <span class="clinic-status-dot" aria-hidden="true"></span>
            {{ ucfirst($appointment->status) }}
        </span>
    </td>
    <td>
        <div class="clinic-row-actions">
            <a class="clinic-action-button clinic-action-view" href="{{ route('appointments.show', $appointment) }}" data-appointment-view>
                <i class="bi bi-eye" aria-hidden="true"></i> View
            </a>
        </div>
    </td>
</tr>
