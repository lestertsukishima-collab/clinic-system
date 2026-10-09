<x-app-layout>
    <x-slot name="header">
        <div class="clinic-dashboard-heading">
            <div>
                <p class="clinic-eyebrow mb-1">DOCTOR WORKSPACE</p>
                <h1 class="clinic-page-title">My clinical workspace</h1>
            </div>
            <a href="{{ route('prescriptions.index') }}" class="clinic-primary-button"><i class="bi bi-prescription2" aria-hidden="true"></i> My prescriptions</a>
        </div>
    </x-slot>

    <section class="clinic-admin-page">
        <div class="clinic-admin-container">
            <div class="clinic-admin-welcome clinic-doctor-welcome">
                <div>
                    <p class="clinic-eyebrow mb-2">{{ $doctor?->specialization ?? 'YOUR PRACTICE' }}</p>
                    <h2>Welcome back, {{ $doctor?->display_name ?? auth()->user()->name }}</h2>
                    <p>Your schedule, patient requests, and treatment records in one place.</p>
                </div>
                <div class="clinic-admin-date">
                    <span class="clinic-admin-date-icon" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                    <span><small>{{ config('clinic.timezone') }}</small><strong>{{ $clinicToday->format('D, M j, Y') }}</strong></span>
                </div>
            </div>

            @unless($doctor)
                <div class="clinic-alert clinic-alert-error" role="alert">Your doctor profile is not linked yet. Please contact the clinic administrator to set up your schedule.</div>
            @endunless

            <div class="clinic-admin-stat-grid">
                <a href="{{ route('appointments.index', ['date' => $clinicToday->toDateString()]) }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline"><span class="clinic-admin-stat-icon clinic-stat-blue" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span><span class="clinic-admin-stat-period">TODAY</span></div>
                    <p>Today's appointments</p><strong>{{ $todayCount }}</strong><small>on your schedule today</small>
                </a>
                <a href="{{ route('appointments.index', ['status' => 'pending']) }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline"><span class="clinic-admin-stat-icon clinic-stat-gold" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span><span class="clinic-admin-stat-period">NEEDS REVIEW</span></div>
                    <p>Pending requests</p><strong>{{ $pendingCount }}</strong><small>assigned to you</small>
                </a>
                <a href="{{ route('appointments.index', ['date' => $clinicToday->toDateString(), 'status' => 'completed']) }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline"><span class="clinic-admin-stat-icon clinic-stat-mint" aria-hidden="true"><i class="bi bi-check2-circle"></i></span><span class="clinic-admin-stat-period">TODAY</span></div>
                    <p>Completed visits</p><strong>{{ $completedTodayCount }}</strong><small>from today's appointments</small>
                </a>
                <a href="{{ route('prescriptions.index') }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline"><span class="clinic-admin-stat-icon clinic-stat-lilac" aria-hidden="true"><i class="bi bi-prescription2"></i></span><span class="clinic-admin-stat-period">RECORDS</span></div>
                    <p>Prescriptions</p><strong>{{ $prescriptionCount }}</strong><small>linked to your appointments</small>
                </a>
            </div>

            <div class="clinic-doctor-content-grid">
                <section class="clinic-appointments-card" aria-labelledby="doctor-schedule-title">
                    <div class="clinic-card-heading">
                        <div>
                            <p class="clinic-eyebrow mb-1">YOUR SCHEDULE</p>
                            <h2 id="doctor-schedule-title">{{ $schedule === 'today' ? "Today's appointments" : 'All appointments' }}</h2>
                            <p class="clinic-card-description">{{ $schedule === 'today' ? 'Today’s visits, earliest first.' : 'Past and upcoming visits, newest date first. All statuses are included.' }} Times are in {{ config('clinic.timezone') }}.</p>
                        </div>
                        <a href="{{ route('appointments.index') }}" class="clinic-admin-view-all">Full schedule <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <nav class="clinic-list-filters" aria-label="Appointment view">
                        <a href="{{ route('dashboard') }}" class="clinic-action-button {{ $schedule === 'all' ? 'clinic-action-view' : '' }}" @if($schedule === 'all') aria-current="page" @endif>All appointments ({{ $appointmentCount }})</a>
                        <a href="{{ route('dashboard', ['schedule' => 'today']) }}" class="clinic-action-button {{ $schedule === 'today' ? 'clinic-action-view' : '' }}" @if($schedule === 'today') aria-current="page" @endif>Today ({{ $todayCount }})</a>
                    </nav>
                    <div class="clinic-table-wrap">
                        <table class="clinic-appointments-table clinic-doctor-table">
                            <thead><tr><th scope="col">Scheduled visit</th><th scope="col">Requested on</th><th scope="col">Patient / service</th><th scope="col">Status</th><th scope="col" class="clinic-actions-heading">Visit</th></tr></thead>
                            <tbody>
                                @forelse($scheduleAppointments as $appointment)
                                    <tr>
                                        <td data-label="Scheduled visit"><div class="clinic-date-cell"><span>{{ $appointment->local_appointment_date->format('M d, Y') }}</span><small><time datetime="{{ $appointment->local_appointment_date->toIso8601String() }}">{{ $appointment->local_appointment_date->format('g:i A') }}</time></small></div></td>
                                        <td data-label="Requested on"><div class="clinic-date-cell"><span>{{ $appointment->local_requested_at->format('M d, Y') }}</span><small>{{ $appointment->local_requested_at->format('g:i:s A') }}</small></div></td>
                                        <td data-label="Patient / service"><div class="clinic-doctor-patient"><strong>{{ $appointment->patient->name }}</strong><small>{{ $appointment->service->name }}</small></div></td>
                                        <td data-label="Status"><span class="clinic-status clinic-status-{{ $appointment->status }}"><span class="clinic-status-dot" aria-hidden="true"></span>{{ ucfirst($appointment->status) }}</span></td>
                                        <td data-label="Visit"><a href="{{ route('appointments.show', $appointment) }}" class="clinic-action-button clinic-action-view" aria-label="Open visit for {{ $appointment->patient->name }}" data-appointment-view>Open visit</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="clinic-empty-state"><span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span><h3>{{ $schedule === 'today' ? 'No appointments today' : 'No appointments assigned yet' }}</h3><p>{{ $schedule === 'today' ? 'Choose All appointments to see past and upcoming visits.' : 'Appointments assigned to you will appear here.' }}</p><a href="{{ route('appointments.index') }}" class="clinic-admin-view-all">View my appointments</a></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($scheduleAppointments->hasPages())
                        <div class="clinic-pagination">{{ $scheduleAppointments->links() }}</div>
                    @endif
                </section>

                <div class="clinic-doctor-side-panels">
                    <section class="clinic-admin-panel clinic-doctor-next" aria-labelledby="next-visit-title">
                        <div class="clinic-admin-panel-heading"><div><p class="clinic-eyebrow mb-1">LOOKING AHEAD</p><h2 id="next-visit-title">Next confirmed visit</h2></div></div>
                        <div class="clinic-doctor-next-summary">
                            @if($nextAppointment)
                                <p class="clinic-doctor-next-time">{{ $nextAppointment->local_appointment_date->format('M j · g:i A') }}</p>
                                <div class="clinic-doctor-patient"><strong>{{ $nextAppointment->patient->name }}</strong><small>{{ $nextAppointment->service->name }}</small></div>
                                <a href="{{ route('appointments.show', $nextAppointment) }}" class="clinic-admin-view-all" data-appointment-view>Review visit <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                            @else
                                <p class="clinic-card-description">No upcoming confirmed visits. Review pending requests to plan your schedule.</p>
                            @endif
                        </div>
                    </section>

                    <section class="clinic-admin-panel" aria-labelledby="doctor-pending-title">
                        <div class="clinic-admin-panel-heading"><div><p class="clinic-eyebrow mb-1">AWAITING YOUR REVIEW</p><h2 id="doctor-pending-title">Pending requests</h2></div><span class="clinic-count-pill">{{ $pendingCount }}</span></div>
                        <div class="clinic-doctor-pending-list">
                            @forelse($pendingAppointments as $appointment)
                                <div class="clinic-doctor-request">
                                    <div class="clinic-doctor-patient"><strong>{{ $appointment->patient->name }}</strong><small>{{ $appointment->service->name }}</small><small>{{ $appointment->local_appointment_date->format('M j, Y · g:i A') }}</small></div>
                                    <div class="clinic-row-actions">
                                        <a href="{{ route('appointments.show', $appointment) }}" class="clinic-action-button clinic-action-view" data-appointment-view>{{ $appointment->appointment_date->isFuture() ? 'Review' : 'Reschedule and confirm' }}</a>
                                        @if($appointment->appointment_date->isFuture())
                                            <form method="POST" action="{{ route('appointments.confirm', $appointment) }}">
                                                @csrf
                                                <button type="submit" class="clinic-action-button" aria-label="Confirm appointment for {{ $appointment->patient->name }}">Confirm</button>
                                            </form>
                                        @else
                                            <span class="clinic-doctor-overdue">Past start time · choose a new visit time before confirming</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="clinic-card-description clinic-doctor-pending-empty">You're up to date. No requests need your review.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('appointments.index', ['status' => 'pending']) }}" class="clinic-admin-view-all">View all pending requests <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </section>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
