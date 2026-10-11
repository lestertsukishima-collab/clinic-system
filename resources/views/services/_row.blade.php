<tr>
    <td>
        <div class="clinic-person-cell">
            <span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-heart-pulse-fill"></i></span>
            <span class="clinic-person-name">{{ $service->name }}</span>
        </div>
    </td>
    <td class="clinic-service-cell">₱{{ number_format((float) $service->price, 2) }}</td>
    <td>{{ $service->appointments_count }}</td>
    <td>
        <div class="clinic-row-actions">
            <a href="{{ route('services.show', $service) }}" class="clinic-action-button clinic-action-view">
                <i class="bi bi-eye" aria-hidden="true"></i> View
            </a>
        </div>
    </td>
</tr>
