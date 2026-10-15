@props(['unavailabilities', 'affectedAppointments'])

@foreach($affectedAppointments as $affectedAppointment)
    <div class="clinic-alert clinic-alert-error" role="alert">
        <span>
            {{ $affectedAppointment->doctor->display_name }} is unavailable for
            {{ auth()->user()->role === 'patient' ? 'your appointment' : $affectedAppointment->patient->name . "'s appointment" }}
            on {{ $affectedAppointment->local_appointment_date->format('M j, Y \a\t g:i A') }}.
            {{ auth()->user()->role === 'patient' ? 'Please contact the clinic to arrange another time or doctor.' : 'Please arrange another time or doctor with the patient.' }}
            <a href="{{ route('appointments.show', $affectedAppointment) }}" class="clinic-admin-view-all">Review appointment</a>
        </span>
    </div>
@endforeach

@if($unavailabilities->isNotEmpty())
    <section class="clinic-admin-panel" aria-labelledby="doctor-availability-title">
        <div class="clinic-admin-panel-heading"><h2 id="doctor-availability-title">Doctor availability notices</h2></div>
        <p class="clinic-card-description">Appointments cannot be booked during these periods. Times are in {{ config('clinic.timezone') }}.</p>
        <ul class="clinic-patient-help">
            @foreach($unavailabilities as $unavailability)
                <li>
                    <strong>{{ $unavailability->doctor->display_name }}</strong> is unavailable from
                    {{ $unavailability->starts_at->setTimezone(config('clinic.timezone'))->format('M j, Y g:i A') }}
                    to {{ $unavailability->ends_at->setTimezone(config('clinic.timezone'))->format('M j, Y g:i A') }}.
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('doctors.show', $unavailability->doctor) }}" class="clinic-admin-view-all">Manage availability</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
