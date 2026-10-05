<x-app-layout>
    <x-slot name="header">
        <div class="clinic-dashboard-heading">
            <div>
                <p class="clinic-eyebrow mb-1">ADMINISTRATOR VIEW</p>
                <h1 class="clinic-page-title">Clinic overview</h1>
            </div>
            <a href="{{ route('appointments.index') }}" class="clinic-primary-button">
                <i class="bi bi-calendar2-week" aria-hidden="true"></i> Review appointments
            </a>
        </div>
    </x-slot>

    <section class="clinic-admin-page">
        <div class="clinic-admin-container">
            <div class="clinic-admin-welcome">
                <div>
                    <p class="clinic-eyebrow clinic-eyebrow-light mb-2">CLINIC ACTIVITY</p>
                    <h2>Welcome back, {{ auth()->user()->name }}</h2>
                    <p>Here’s how Happy Clinic is doing. Appointment requests are counted by the date they were submitted.</p>
                </div>
                <div class="clinic-admin-date">
                    <span class="clinic-admin-date-icon" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
                    <span>
                        <small>Today</small>
                        <strong>{{ now()->format('D, M j, Y') }}</strong>
                    </span>
                </div>
            </div>

            <div class="clinic-admin-stat-grid">
                <article class="clinic-admin-stat-card">
                    <div class="clinic-admin-stat-topline">
                        <span class="clinic-admin-stat-icon clinic-stat-mint" aria-hidden="true"><i class="bi bi-calendar2-plus"></i></span>
                        <span class="clinic-admin-stat-period">THIS MONTH</span>
                    </div>
                    <p>Appointment requests</p>
                    <strong>{{ $requestsThisMonth }}</strong>
                    <small>from {{ $patientsRequestingThisMonth }} {{ \Illuminate\Support\Str::plural('patient', $patientsRequestingThisMonth) }}</small>
                </article>

                <a href="{{ route('appointments.index') }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline">
                        <span class="clinic-admin-stat-icon clinic-stat-gold" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                        <span class="clinic-admin-stat-arrow" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span>
                    </div>
                    <p>Pending requests</p>
                    <strong>{{ $pendingRequests }}</strong>
                    <small>need a clinic team response</small>
                </a>

                <article class="clinic-admin-stat-card">
                    <div class="clinic-admin-stat-topline">
                        <span class="clinic-admin-stat-icon clinic-stat-blue" aria-hidden="true"><i class="bi bi-people"></i></span>
                        <span class="clinic-admin-stat-period">REGISTERED</span>
                    </div>
                    <p>Patients</p>
                    <strong>{{ $registeredPatients }}</strong>
                    <small>patient accounts</small>
                </article>

                <a href="{{ route('doctors.index') }}" class="clinic-admin-stat-card clinic-admin-stat-link">
                    <div class="clinic-admin-stat-topline">
                        <span class="clinic-admin-stat-icon clinic-stat-lilac" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                        <span class="clinic-admin-stat-arrow" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span>
                    </div>
                    <p>Doctors</p>
                    <strong>{{ $doctorCount }}</strong>
                    <small>on the clinic team</small>
                </a>
            </div>

            <div class="clinic-admin-content-grid">
                <section class="clinic-admin-panel clinic-admin-trend-panel" aria-labelledby="request-trend-title">
                    <div class="clinic-admin-panel-heading">
                        <div>
                            <p class="clinic-eyebrow mb-1">REQUEST ACTIVITY</p>
                            <h2 id="request-trend-title">Appointment requests</h2>
                        </div>
                        <span class="clinic-admin-range">Last 6 months</span>
                    </div>

                    <div class="clinic-admin-chart" role="img" aria-label="Appointment request counts over the last six months">
                        @foreach($monthlyRequests as $month)
                            @php($barHeight = max(8, (int) round(($month['count'] / $maxMonthlyRequests) * 100)))
                            <div class="clinic-admin-chart-column" title="{{ $month['count'] }} {{ \Illuminate\Support\Str::plural('request', $month['count']) }} in {{ $month['label'] }}">
                                <span class="clinic-admin-chart-value">{{ $month['count'] }}</span>
                                <div class="clinic-admin-chart-track">
                                    <span class="clinic-admin-chart-bar" style="height: {{ $barHeight }}%"></span>
                                </div>
                                <span class="clinic-admin-chart-label">{{ $month['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="clinic-admin-chart-caption">Requests are based on when patients submitted them, not the appointment date.</p>
                </section>

                <aside class="clinic-admin-panel clinic-admin-shortcuts">
                    <div class="clinic-admin-panel-heading">
                        <div>
                            <p class="clinic-eyebrow mb-1">ADMIN TOOLS</p>
                            <h2>Quick access</h2>
                        </div>
                    </div>
                    <a href="{{ route('appointments.index') }}" class="clinic-admin-shortcut">
                        <span class="clinic-shortcut-icon clinic-stat-mint" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
                        <span><strong>Appointments</strong><small>Review and confirm requests</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('doctors.index') }}" class="clinic-admin-shortcut">
                        <span class="clinic-shortcut-icon clinic-stat-lilac" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                        <span><strong>Doctors</strong><small>Manage clinic accounts</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('services.index') }}" class="clinic-admin-shortcut">
                        <span class="clinic-shortcut-icon clinic-stat-blue" aria-hidden="true"><i class="bi bi-clipboard2-pulse"></i></span>
                        <span><strong>Services</strong><small>Manage clinic services</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </aside>
            </div>

            <section class="clinic-appointments-card clinic-admin-recent" aria-labelledby="recent-requests-title">
                <div class="clinic-card-heading">
                    <div>
                        <p class="clinic-eyebrow mb-1">LATEST ACTIVITY</p>
                        <h2 id="recent-requests-title">Recent appointment requests</h2>
                        <p class="clinic-card-description">The latest requests received by your clinic.</p>
                    </div>
                    <a href="{{ route('appointments.index') }}" class="clinic-admin-view-all">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>

                <div class="clinic-table-wrap">
                    <table class="clinic-appointments-table clinic-admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Patient</th>
                                <th scope="col">Doctor</th>
                                <th scope="col">Service</th>
                                <th scope="col">Requested</th>
                                <th scope="col">Appointment</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="clinic-actions-heading">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAppointments as $appointment)
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
                                            <span>{{ $appointment->created_at->format('M d, Y') }}</span>
                                            <small>{{ $appointment->created_at->format('g:i A') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="clinic-date-cell">
                                            <span>{{ $appointment->appointment_date->format('M d, Y') }}</span>
                                            <small>{{ $appointment->appointment_date->format('g:i A') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="clinic-status clinic-status-{{ strtolower($appointment->status) }}">
                                            <span class="clinic-status-dot" aria-hidden="true"></span>{{ ucfirst($appointment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="clinic-row-actions">
                                            <a class="clinic-action-button clinic-action-view" href="{{ route('appointments.show', $appointment) }}">View</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="clinic-empty-state">
                                            <span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span>
                                            <h3>No requests yet</h3>
                                            <p>New appointment requests will appear here.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($recentAppointments->hasPages())
                    <div class="clinic-pagination">{{ $recentAppointments->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
