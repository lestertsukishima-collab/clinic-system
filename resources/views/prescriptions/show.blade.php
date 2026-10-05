<x-app-layout>
    <x-slot name="header">
        <div class="clinic-record-heading">
            <div>
                <p class="clinic-eyebrow mb-1">TREATMENT RECORD</p>
                <h1 class="clinic-page-title">Prescription #{{ $prescription->id }}</h1>
            </div>
            <a href="{{ route('prescriptions.index') }}" class="clinic-back-link">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to prescriptions
            </a>
        </div>
    </x-slot>

    <section class="clinic-record-page">
        <div class="clinic-record-container clinic-record-container-narrow">
            @if(session('success'))
                <div class="clinic-alert clinic-alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') }}</span>
                </div>
            @endif

            <article class="clinic-detail-card clinic-prescription-detail">
                <div class="clinic-detail-card-header">
                    <div class="clinic-detail-heading-icon clinic-prescription-heading-icon" aria-hidden="true">
                        <i class="bi bi-prescription2"></i>
                    </div>
                    <div class="clinic-detail-heading-copy">
                        <p class="clinic-eyebrow mb-1">PRESCRIPTION SUMMARY</p>
                        <h2>{{ $prescription->appointment->patient->name }}</h2>
                        <p>Appointment on {{ $prescription->appointment->appointment_date->format('M d, Y') }}</p>
                    </div>
                    <span class="clinic-prescription-reference">RX-{{ str_pad((string) $prescription->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>

                <div class="clinic-prescription-meta">
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-person-badge" aria-hidden="true"></i> Prescribed by</span>
                        <strong>{{ $prescription->appointment->doctor->display_name }}</strong>
                        <small>{{ $prescription->appointment->doctor->specialization }}</small>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-calendar2-check" aria-hidden="true"></i> Appointment</span>
                        <strong>{{ $prescription->appointment->appointment_date->format('M d, Y') }}</strong>
                        <small>{{ $prescription->appointment->appointment_date->format('g:i A') }}</small>
                    </div>
                </div>

                <section class="clinic-prescription-section">
                    <span class="clinic-detail-label"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Diagnosis</span>
                    <p>{{ $prescription->diagnosis }}</p>
                </section>

                <section class="clinic-prescription-section clinic-medicine-section">
                    <span class="clinic-detail-label"><i class="bi bi-capsule" aria-hidden="true"></i> Medicines and dosage</span>
                    <p>{{ $prescription->medicines }}</p>
                </section>

                <section class="clinic-prescription-section">
                    <span class="clinic-detail-label"><i class="bi bi-info-circle" aria-hidden="true"></i> Instructions</span>
                    <p>{{ $prescription->instructions ?: 'No additional instructions provided.' }}</p>
                </section>

                <div class="clinic-detail-actions clinic-prescription-actions">
                    <a href="{{ route('prescriptions.print', $prescription) }}" class="clinic-action-button">
                        <i class="bi bi-printer" aria-hidden="true"></i> Print
                    </a>
                    <a href="{{ route('prescriptions.edit', $prescription) }}" class="clinic-primary-button">
                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit prescription
                    </a>
                    <form method="POST" action="{{ route('prescriptions.destroy', $prescription) }}" onsubmit="return confirm('Delete this prescription?')">
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
