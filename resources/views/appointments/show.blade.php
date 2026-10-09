<x-app-layout>
    <x-slot name="header">
        <div class="clinic-record-heading">
            <div>
                <p class="clinic-eyebrow mb-1">APPOINTMENT DETAILS</p>
                <h1 class="clinic-page-title">Appointment #{{ $appointment->id }}</h1>
            </div>
            <a href="{{ route('appointments.index') }}" class="clinic-back-link" data-appointment-return>
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

            <x-input-error :messages="$errors->get('appointment_date')" class="mb-4" />

            <article class="clinic-detail-card">
                <div class="clinic-detail-card-header">
                    <div class="clinic-detail-heading-icon" aria-hidden="true"><i class="bi bi-calendar2-heart"></i></div>
                    <div class="clinic-detail-heading-copy">
                        <p class="clinic-eyebrow mb-1">VISIT SUMMARY</p>
                        <h2>{{ $appointment->service->name }}</h2>
                        <p>{{ $appointment->local_appointment_date->format('l, F j, Y') }} at {{ $appointment->local_appointment_date->format('g:i A') }}</p>
                    </div>
                    <span class="clinic-status clinic-status-{{ strtolower($appointment->status) }}">
                        <span class="clinic-status-dot" aria-hidden="true"></span>
                        {{ ucfirst($appointment->status) }}
                    </span>
                </div>

                @if(in_array(auth()->user()->role, ['admin', 'doctor'], true) && $appointment->status === 'pending')
                    <section class="clinic-detail-notes" aria-labelledby="confirm-request-title">
                        <h3 id="confirm-request-title" class="clinic-detail-label">Confirm this request</h3>
                        <form id="appointment-confirmation-form" method="POST" action="{{ route('appointments.confirm', $appointment) }}" class="clinic-record-form">
                            @csrf
                            @unless($appointment->appointment_date->isFuture())
                                <p class="clinic-card-description">The scheduled time has passed. Agree on a new time with the patient, then reschedule and confirm this request. The original request date stays unchanged.</p>
                                <div>
                                    <label for="confirmation-appointment-date" class="block text-sm font-medium text-gray-700">New visit date and time ({{ config('clinic.timezone') }})</label>
                                    <input id="confirmation-appointment-date" name="appointment_date" type="datetime-local" required
                                        min="{{ now(config('clinic.timezone'))->addMinute()->format('Y-m-d\TH:i') }}"
                                        value="{{ old('appointment_date') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <p class="mt-1 text-sm text-gray-600">Allow {{ config('clinic.appointment_duration_minutes') }} minutes. Overlapping bookings are unavailable.</p>
                                </div>
                            @endunless
                            <div class="clinic-form-actions">
                                <button class="clinic-primary-button" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ $appointment->appointment_date->isFuture() ? 'Confirm appointment' : 'Reschedule and confirm' }}</button>
                            </div>
                        </form>
                    </section>
                @endif

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
                        <span class="clinic-detail-label"><i class="bi bi-clock" aria-hidden="true"></i> Scheduled visit</span>
                        <strong>{{ $appointment->local_appointment_date->format('M d, Y') }}</strong>
                        <small>{{ $appointment->local_appointment_date->format('g:i A') }} &middot; {{ config('clinic.timezone') }}</small>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Requested on</span>
                        <strong>{{ $appointment->local_requested_at->format('M d, Y') }}</strong>
                        <small>{{ $appointment->local_requested_at->format('g:i:s A') }} &middot; {{ config('clinic.timezone') }}</small>
                    </div>
                </div>

                <div class="clinic-detail-notes">
                    <span class="clinic-detail-label"><i class="bi bi-journal-medical" aria-hidden="true"></i> Notes</span>
                    <p>{{ $appointment->notes ?: 'No additional notes were provided.' }}</p>
                </div>

                @if(auth()->user()->role === 'patient')
                    <section class="clinic-detail-notes" aria-labelledby="patient-visit-status-title">
                        <h3 id="patient-visit-status-title" class="clinic-detail-label">About your appointment</h3>
                        <p>{{ match ($appointment->status) {
                            'pending' => 'Your request is awaiting clinic approval. You can edit or cancel it while it is pending.',
                            'confirmed' => 'Your appointment is confirmed. Contact the clinic if you need to change or cancel this visit.',
                            'completed' => 'Your consultation has been marked completed. Any recorded prescriptions are listed below.',
                            'cancelled' => 'This appointment was cancelled. You can request a new appointment when you are ready.',
                            default => 'Check the appointment status above for updates.',
                        } }}</p>
                    </section>
                    @if($appointment->status === 'completed')
                        <section class="clinic-detail-notes" aria-labelledby="patient-visit-prescriptions-title">
                            <h3 id="patient-visit-prescriptions-title" class="clinic-detail-label">Your prescriptions</h3>
                            @forelse($appointment->prescriptions as $prescription)
                                <p><a href="{{ route('patient-prescriptions.show', $prescription) }}" class="clinic-admin-view-all">Prescription #{{ $prescription->id }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a></p>
                            @empty
                                <p>No prescription was recorded for this visit.</p>
                            @endforelse
                        </section>
                    @endif
                @endif

                @if((auth()->user()->role === 'admin' && in_array($appointment->status, ['pending', 'confirmed'], true)) || (auth()->user()->role === 'patient' && $appointment->status === 'pending'))
                    <div class="clinic-detail-actions">
                        @if((auth()->user()->role === 'admin' && in_array($appointment->status, ['pending', 'confirmed'], true)) || (auth()->user()->role === 'patient' && $appointment->status === 'pending'))
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
                    </div>
                @endif

                @if(in_array(auth()->user()->role, ['admin', 'doctor'], true))
                    @if($appointment->status === 'pending')
                        <div class="clinic-detail-actions">
                            <button type="submit" form="appointment-confirmation-form" class="clinic-primary-button"><i class="bi bi-check-lg" aria-hidden="true"></i> {{ $appointment->appointment_date->isFuture() ? 'Confirm appointment' : 'Reschedule and confirm' }}</button>
                        </div>
                    @endif
                    @if(in_array($appointment->status, ['confirmed', 'completed'], true))
                        <div class="clinic-detail-actions">
                            <a href="{{ route('prescriptions.create', ['appointment_id' => $appointment->id]) }}" class="clinic-action-button clinic-action-view"><i class="bi bi-prescription2" aria-hidden="true"></i> Write prescription</a>
                            @if($appointment->status === 'confirmed' && ! $appointment->appointment_date->isFuture())
                                <form method="POST" action="{{ route('appointments.complete', $appointment) }}" onsubmit="return confirm('Mark this visit as completed? Confirm that the consultation has finished.');">
                                    @csrf
                                    <button type="submit" class="clinic-primary-button"><i class="bi bi-check2-circle" aria-hidden="true"></i> Mark completed</button>
                                </form>
                            @endif
                        </div>
                    @endif
                    <section class="clinic-detail-notes" aria-labelledby="visit-prescriptions-title">
                        <h3 id="visit-prescriptions-title" class="clinic-detail-label">Prescriptions for this visit</h3>
                        @forelse($appointment->prescriptions as $prescription)
                            <p><a href="{{ route('prescriptions.show', $prescription) }}" class="clinic-admin-view-all">Prescription #{{ $prescription->id }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a></p>
                        @empty
                            <p>No prescriptions recorded for this visit.</p>
                        @endforelse
                    </section>
                @endif
            </article>
        </div>
    </section>
</x-app-layout>
