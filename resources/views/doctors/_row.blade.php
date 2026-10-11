<tr>
    <td>
        <div class="clinic-person-cell">
            <span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-person-badge-fill"></i></span>
            <span class="clinic-person-name">{{ $doctor->display_name }}</span>
        </div>
    </td>
    <td>{{ $doctor->user->email }}</td>
    <td class="clinic-service-cell">{{ $doctor->specialization }}</td>
    <td>{{ $doctor->appointments_count }}</td>
    <td>
        <div class="clinic-row-actions">
            <a class="clinic-action-button clinic-action-view" href="{{ route('doctors.show', $doctor) }}">
                <i class="bi bi-eye" aria-hidden="true"></i> View
            </a>
        </div>
    </td>
</tr>
