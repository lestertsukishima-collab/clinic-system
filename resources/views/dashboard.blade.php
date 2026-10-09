<x-app-layout>
    <x-slot name="header">
        <div class="clinic-dashboard-heading">
            <div>
                <p class="clinic-eyebrow mb-1">CLINIC MANAGEMENT</p>
                <h1 class="clinic-page-title">Dashboard</h1>
            </div>

            @if(auth()->user()->role !== 'doctor')
                <a href="{{ route('appointments.create') }}" class="clinic-primary-button">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    <span>Book appointment</span>
                </a>
            @endif
        </div>
    </x-slot>

    <section class="clinic-dashboard-page">
        <div class="clinic-dashboard-container">
            <div class="clinic-welcome-card">
                <div class="clinic-welcome-copy">
                    <div class="clinic-welcome-icon" aria-hidden="true">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div>
                        <p class="clinic-eyebrow clinic-eyebrow-light mb-2">YOUR CLINIC, AT A GLANCE</p>
                        <h2>Welcome back, {{ auth()->user()->name }}</h2>
                        <p class="clinic-welcome-description">
                            Review your appointment schedule and keep care moving smoothly.
                        </p>
                    </div>
                </div>

                <div class="clinic-total-card" aria-label="Total appointments: {{ $appointments->total() }}">
                    <span class="clinic-total-label">Appointments</span>
                    <strong>{{ $appointments->total() }}</strong>
                    <span class="clinic-total-caption">in your schedule</span>
                </div>
            </div>

            @if(session('success'))
                <div class="clinic-alert clinic-alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('status'))
                <div class="clinic-alert clinic-alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="clinic-alert clinic-alert-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <section class="clinic-appointments-card" aria-labelledby="clinic-appointments-title">
                <div class="clinic-card-heading">
                    <div>
                        <p class="clinic-eyebrow mb-1">SCHEDULE</p>
                        <h2 id="clinic-appointments-title">Your appointments</h2>
                        <p class="clinic-card-description">Appointments are ordered by the most recent date.</p>
                    </div>
                    <span class="clinic-count-pill">
                        <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                        {{ $appointments->total() }} {{ \Illuminate\Support\Str::plural('appointment', $appointments->total()) }}
                    </span>
                </div>

                <div class="clinic-table-wrap">
                    <table class="clinic-appointments-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ auth()->user()->role === 'doctor' ? 'Patient' : 'Doctor' }}</th>
                                <th scope="col">Service</th>
                                <th scope="col">Date &amp; time</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="clinic-actions-heading">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentsTableBody">
                            @forelse($appointments as $appointment)
                                <tr>
                                    <td>
                                        <div class="clinic-person-cell">
                                            <span class="clinic-person-avatar" aria-hidden="true">
                                                <i class="bi bi-person-fill"></i>
                                            </span>
                                            <span class="clinic-person-name">
                                                @if(auth()->user()->role === 'doctor')
                                                    {{ $appointment->patient->name ?? ($appointment->patient_id ? 'Patient #' . $appointment->patient_id : 'Walk-in patient') }}
                                                @else
                                                    {{ $appointment->doctor->display_name ?? 'Doctor #' . $appointment->doctor_id }}
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="clinic-service-cell">{{ $appointment->service->name ?? '—' }}</td>
                                    <td>
                                        <div class="clinic-date-cell">
                                            <span>{{ \Carbon\Carbon::parse($appointment->local_appointment_date)->format('M d, Y') }}</span>
                                            <small>{{ \Carbon\Carbon::parse($appointment->local_appointment_date)->format('g:i A') }}</small>
                                        </div>
                                        </td>
                                        <td>
                                            @php($status = strtolower($appointment->status))
                                            @if($status === 'pending' && in_array(auth()->user()->role, ['admin', 'doctor'], true))
                                                <form method="POST" action="{{ route('appointments.confirm', $appointment) }}" class="clinic-status-form">
                                                    @csrf
                                                    <button class="clinic-status clinic-status-pending clinic-status-button" type="submit" title="Confirm this appointment and notify the patient" aria-label="Confirm appointment for {{ $appointment->patient->name }}">
                                                        <span class="clinic-status-dot" aria-hidden="true"></span>
                                                        Pending
                                                        <i class="bi bi-arrow-right-short clinic-status-arrow" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="clinic-status clinic-status-{{ $status }}">
                                                    <span class="clinic-status-dot" aria-hidden="true"></span>
                                                    {{ ucfirst($status) }}
                                                </span>
                                            @endif
                                        </td>
                                    <td>
                                        <div class="clinic-row-actions">
                                            <a class="clinic-action-button clinic-action-view" href="{{ route('appointments.show', $appointment) }}">
                                                View
                                            </a>
                                            @if((auth()->user()->role === 'admin' && in_array($appointment->status, ['pending', 'confirmed'], true)) || (auth()->user()->role === 'patient' && $appointment->status === 'pending'))
                                                <a class="clinic-action-button" href="{{ route('appointments.edit', $appointment) }}">Edit</a>
                                                <form method="POST" action="{{ route('appointments.destroy', $appointment) }}" onsubmit="return confirm('Cancel this appointment?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="clinic-action-button clinic-action-cancel" type="submit">Cancel</button>
                                                </form>
                                            @endif
                                            </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="clinic-empty-state">
                                            <span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span>
                                            <h3>No appointments yet</h3>
                                            <p>Your upcoming clinic appointments will appear here.</p>
                                            @if(auth()->user()->role === 'patient')
                                                <a href="{{ route('appointments.create') }}" class="clinic-primary-button clinic-empty-action">
                                                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                                    Book an appointment
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
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
