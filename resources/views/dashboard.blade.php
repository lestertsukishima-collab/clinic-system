<x-app-layout>
    <x-slot name="header">
        <div class="clinic-dashboard-heading">
            <div>
                <p class="clinic-eyebrow mb-1">PATIENT WORKSPACE</p>
                <h1 class="clinic-page-title">My care</h1>
            </div>
            <a href="{{ route('appointments.create') }}" class="clinic-primary-button"><i class="bi bi-plus-lg" aria-hidden="true"></i> Request appointment</a>
        </div>
    </x-slot>

    <section class="clinic-admin-page">
        <div class="clinic-admin-container">
            <div class="clinic-admin-welcome">
                <div>
                    <p class="clinic-eyebrow mb-2">YOUR HEALTH, YOUR WORKSPACE</p>
                    <h2>Welcome back, {{ auth()->user()->name }}</h2>
                    <p>Request a visit, follow its status, and find your prescriptions after your consultation.</p>
                </div>
                <div class="clinic-admin-date">
                    <span class="clinic-admin-date-icon" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                    <span><small>{{ config('clinic.timezone') }}</small><strong>{{ now(config('clinic.timezone'))->format('D, M j, Y') }}</strong></span>
                </div>
            </div>

            @if(session('success') || session('status'))
                <div class="clinic-alert clinic-alert-success" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') ?? session('status') }}</span></div>
            @endif
            @if(session('error'))
                <div class="clinic-alert clinic-alert-error" role="alert"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span>{{ session('error') }}</span></div>
            @endif

            <div class="clinic-admin-stat-grid">
                <a href="{{ route('appointments.index') }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline"><span class="clinic-admin-stat-icon clinic-stat-blue" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span><span class="clinic-admin-stat-period">ALL DATES</span></div>
                    <p>My appointments</p><strong>{{ $appointments->total() }}</strong><small>past and upcoming visits</small>
                </a>
                <a href="{{ route('appointments.index', ['status' => 'pending']) }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline"><span class="clinic-admin-stat-icon clinic-stat-gold" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span><span class="clinic-admin-stat-period">AWAITING CONFIRMATION</span></div>
                    <p>Pending requests</p><strong>{{ $pendingCount }}</strong><small>waiting for the clinic's response</small>
                </a>
                <a href="{{ route('appointments.index', ['status' => 'completed']) }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline"><span class="clinic-admin-stat-icon clinic-stat-mint" aria-hidden="true"><i class="bi bi-check2-circle"></i></span><span class="clinic-admin-stat-period">VISIT HISTORY</span></div>
                    <p>Completed visits</p><strong>{{ $completedCount }}</strong><small>consultations marked completed</small>
                </a>
                <a href="{{ route('patient-prescriptions.index') }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline"><span class="clinic-admin-stat-icon clinic-stat-lilac" aria-hidden="true"><i class="bi bi-prescription2"></i></span><span class="clinic-admin-stat-period">MY RECORDS</span></div>
                    <p>Prescriptions</p><strong>{{ $prescriptionCount }}</strong><small>from your completed visits</small>
                </a>
            </div>

            <div class="clinic-admin-content-grid">
                <section class="clinic-admin-panel clinic-patient-next" aria-labelledby="patient-next-title">
                    <div class="clinic-admin-panel-heading"><div><p class="clinic-eyebrow mb-1">LOOKING AHEAD</p><h2 id="patient-next-title">Next confirmed appointment</h2></div></div>
                    @if($nextAppointment)
                        <div class="clinic-patient-next-details">
                            <div class="clinic-patient-next-date"><i class="bi bi-calendar2-check" aria-hidden="true"></i><time datetime="{{ $nextAppointment->local_appointment_date->toIso8601String() }}">{{ $nextAppointment->local_appointment_date->format('D, M j, Y') }}<small>{{ $nextAppointment->local_appointment_date->format('g:i A') }} &middot; {{ config('clinic.timezone') }}</small></time></div>
                            <div class="clinic-doctor-patient"><strong>{{ $nextAppointment->doctor->display_name }}</strong><small>{{ $nextAppointment->doctor->specialization }} &middot; {{ $nextAppointment->service->name }}</small></div>
                            <a href="{{ route('appointments.show', $nextAppointment) }}" class="clinic-admin-view-all" data-appointment-view>View appointment details <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        </div>
                    @else
                        <p class="clinic-card-description">No upcoming confirmed appointment.</p>
                        <p class="clinic-admin-chart-caption">{{ $pendingCount > 0 ? 'Your pending requests are still waiting for confirmation. Check their status below.' : 'Request an appointment when you are ready to plan your next visit.' }}</p>
                        <a href="{{ route($pendingCount > 0 ? 'appointments.index' : 'appointments.create', $pendingCount > 0 ? ['status' => 'pending'] : []) }}" class="clinic-admin-view-all">{{ $pendingCount > 0 ? 'Review pending requests' : 'Request an appointment' }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </section>
                <aside class="clinic-admin-panel" aria-labelledby="patient-request-help-title">
                    <div class="clinic-admin-panel-heading"><div><p class="clinic-eyebrow mb-1">YOUR NEXT STEPS</p><h2 id="patient-request-help-title">Managing your visits</h2></div></div>
                    <dl class="clinic-patient-help">
                        <div><dt>While your request is pending</dt><dd>You can edit the details or cancel it. A request becomes a confirmed appointment when the clinic approves it.</dd></div>
                        <div><dt>Once your appointment is confirmed</dt><dd>Contact the clinic if you need to change or cancel your visit.</dd></div>
                        <div><dt>After your consultation</dt><dd>Your recorded prescriptions become available when the visit is marked completed.</dd></div>
                    </dl>
                </aside>
            </div>

            <section class="clinic-appointments-card" aria-labelledby="clinic-appointments-title">
                <div class="clinic-card-heading">
                    <div><p class="clinic-eyebrow mb-1">YOUR COMPLETE SCHEDULE</p><h2 id="clinic-appointments-title">All my appointments</h2><p class="clinic-card-description">Past and upcoming visits, newest scheduled date first. Requested on shows when you submitted the request. Times are in {{ config('clinic.timezone') }}.</p></div>
                    <a href="{{ route('appointments.index') }}" class="clinic-admin-view-all">Filter appointments <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="clinic-table-wrap">
                    <table class="clinic-appointments-table">
                        <thead><tr><th scope="col">Doctor</th><th scope="col">Service</th><th scope="col">Requested on</th><th scope="col">Scheduled visit</th><th scope="col">Status</th><th scope="col" class="clinic-actions-heading">Actions</th></tr></thead>
                        <tbody id="appointmentsTableBody">
                            @forelse($appointments as $appointment)
                                <tr>
                                    <td><div class="clinic-person-cell"><span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span><span class="clinic-person-name">{{ $appointment->doctor->display_name }}</span></div></td>
                                    <td class="clinic-service-cell">{{ $appointment->service->name }}</td>
                                    <td><div class="clinic-date-cell"><span>{{ $appointment->local_requested_at->format('M d, Y') }}</span><small>{{ $appointment->local_requested_at->format('g:i:s A') }}</small></div></td>
                                    <td><div class="clinic-date-cell"><span>{{ $appointment->local_appointment_date->format('M d, Y') }}</span><small>{{ $appointment->local_appointment_date->format('g:i A') }}</small></div></td>
                                    <td><span class="clinic-status clinic-status-{{ $appointment->status }}"><span class="clinic-status-dot" aria-hidden="true"></span>{{ ucfirst($appointment->status) }}</span></td>
                                    <td>
                                        <div class="clinic-row-actions">
                                            <a class="clinic-action-button clinic-action-view" href="{{ route('appointments.show', $appointment) }}" data-appointment-view>View</a>
                                            @if($appointment->status === 'pending')
                                                <a class="clinic-action-button" href="{{ route('appointments.edit', $appointment) }}">Edit</a>
                                                <form method="POST" action="{{ route('appointments.destroy', $appointment) }}" onsubmit="return confirm('Cancel this appointment request?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="clinic-action-button clinic-action-cancel" type="submit">Cancel</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="clinic-empty-state"><span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span><h3>No appointments yet</h3><p>Start by choosing a doctor, service, and preferred appointment time.</p><a href="{{ route('appointments.create') }}" class="clinic-primary-button clinic-empty-action"><i class="bi bi-plus-lg" aria-hidden="true"></i> Request your first appointment</a></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($appointments->hasPages())
                    <div class="clinic-pagination">{{ $appointments->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
