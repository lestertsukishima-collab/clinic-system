<x-app-layout>
    <x-slot name="header">
        <div class="clinic-record-heading">
            <div>
                <p class="clinic-eyebrow mb-1">APPOINTMENT DETAILS</p>
                <h1 class="clinic-page-title">Appointment #{{ $appointment->id }}</h1>
            </div>
            <a href="{{ route('appointments.index') }}" class="clinic-back-link">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to appointments
            </a>
        </div>
    </x-slot>

    <section class="clinic-record-page">
        <div class="clinic-record-container clinic-record-container-narrow">
            @if(session('success') || session('status'))
                <div class="clinic-alert clinic-alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('success') ?? session('status') }}</span>
                </div>
            @endif

            <article class="clinic-detail-card">
                <div class="clinic-detail-card-header">
                    <div class="clinic-detail-heading-icon" aria-hidden="true"><i class="bi bi-calendar2-heart"></i></div>
                    <div class="clinic-detail-heading-copy">
                        <p class="clinic-eyebrow mb-1">VISIT SUMMARY</p>
                        <h2>{{ $appointment->service->name }}</h2>
                        <p>{{ $appointment->appointment_date->format('l, F j, Y') }} at {{ $appointment->appointment_date->format('g:i A') }}</p>
                    </div>
                    <span class="clinic-status clinic-status-{{ strtolower($appointment->status) }}">
                        <span class="clinic-status-dot" aria-hidden="true"></span>
                        {{ ucfirst($appointment->status) }}
                    </span>
                </div>

                <div class="clinic-detail-grid">
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-person" aria-hidden="true"></i> Patient</span>
                        <strong>{{ $appointment->patient->name }}</strong>
                        <small>{{ $appointment->patient->email }}</small>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-person-badge" aria-hidden="true"></i> Doctor</span>
                        <strong>{{ $appointment->doctor->display_name }}</strong>
                        <small>{{ $appointment->doctor->specialization }}</small>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Clinic service</span>
                        <strong>{{ $appointment->service->name }}</strong>
                        <small>₱{{ number_format((float) $appointment->service->price, 2) }}</small>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-clock" aria-hidden="true"></i> Appointment time</span>
                        <strong>{{ $appointment->appointment_date->format('M d, Y') }}</strong>
                        <small>{{ $appointment->appointment_date->format('g:i A') }}</small>
                    </div>
                </div>

                <div class="clinic-detail-notes">
                    <span class="clinic-detail-label"><i class="bi bi-journal-medical" aria-hidden="true"></i> Notes</span>
                    <p>{{ $appointment->notes ?: 'No additional notes were provided.' }}</p>
                </div>

                @if(auth()->user()->role === 'admin' || (auth()->user()->role === 'patient' && $appointment->status === 'pending') || (auth()->user()->role === 'doctor' && $appointment->status === 'pending'))
                    <div class="clinic-detail-actions">
                        @if(auth()->user()->role === 'admin' || (auth()->user()->role === 'patient' && $appointment->status === 'pending'))
                            <a href="{{ route('appointments.edit', $appointment) }}" class="clinic-action-button">
                                <i class="bi bi-pencil" aria-hidden="true"></i> Edit appointment
                            </a>
                            <form method="POST" action="{{ route('appointments.destroy', $appointment) }}" onsubmit="return confirm('Cancel this appointment?')">
                                @csrf
                                @method('DELETE')
                                <button class="clinic-action-button clinic-action-cancel" type="submit">
                                    <i class="bi bi-x-circle" aria-hidden="true"></i> Cancel appointment
                                </button>
                            </form>
                        @endif
                        @if(in_array(auth()->user()->role, ['admin', 'doctor'], true) && $appointment->status === 'pending')
                            <form method="POST" action="{{ route('appointments.confirm', $appointment) }}">
                                @csrf
                                <button class="clinic-primary-button" type="submit">
                                    <i class="bi bi-check-lg" aria-hidden="true"></i> Confirm appointment
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </article>
        </div>
    </section>
</x-app-layout>
