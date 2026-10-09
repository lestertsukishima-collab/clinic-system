<x-app-layout>
    <x-slot name="header">
        <x-record-heading title="Service Details" eyebrow="CLINIC SERVICES" :back-url="route('services.index')" back-label="Back to services" />
    </x-slot>

    <section class="clinic-record-page">
        <div class="clinic-record-container clinic-record-container-narrow">
            @if(session('success'))
                <div class="clinic-alert clinic-alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="clinic-alert clinic-alert-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span>{{ session('error') }}</span>
                </div>
            @endif

            <article class="clinic-detail-card">
                <div class="clinic-detail-card-header">
                    <div class="clinic-detail-heading-icon" aria-hidden="true"><i class="bi bi-heart-pulse"></i></div>
                    <div class="clinic-detail-heading-copy">
                        <p class="clinic-eyebrow mb-1">SERVICE SUMMARY</p>
                        <h2>{{ $service->name }}</h2>
                        <p>Clinic service</p>
                    </div>
                </div>

                <div class="clinic-detail-grid">
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-cash-coin" aria-hidden="true"></i> Price</span>
                        <strong>&#8369;{{ number_format((float) $service->price, 2) }}</strong>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-calendar2-week" aria-hidden="true"></i> Appointments</span>
                        <strong>{{ $service->appointments_count }}</strong>
                    </div>
                </div>

                <div class="clinic-detail-notes">
                    <span class="clinic-detail-label"><i class="bi bi-card-text" aria-hidden="true"></i> Description</span>
                    <p>{{ $service->description ?: 'No description' }}</p>
                </div>

                <div class="clinic-detail-actions">
                    <a href="{{ route('services.edit', $service) }}" class="clinic-primary-button">
                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit service
                    </a>
                    <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Delete this service?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="clinic-action-button clinic-action-cancel">
                            <i class="bi bi-trash3" aria-hidden="true"></i> Delete
                        </button>
                    </form>
                </div>
            </article>
        </div>
    </section>
</x-app-layout>
