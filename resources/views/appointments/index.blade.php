<x-app-layout>
    <x-slot name="header">
        <div class="clinic-record-heading">
            <div>
                <p class="clinic-eyebrow mb-1">{{ auth()->user()->role === 'patient' ? 'PATIENT WORKSPACE' : 'CLINIC MANAGEMENT' }}</p>
                <h1 class="clinic-page-title">{{ auth()->user()->role === 'patient' ? 'My appointments' : 'Appointments' }}</h1>
            </div>
            @if(in_array(auth()->user()->role, ['patient', 'admin'], true))
                <a href="{{ route('appointments.create') }}" class="clinic-primary-button">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> New appointment
                </a>
            @endif
        </div>
    </x-slot>

    <section class="clinic-record-page">
        <div class="clinic-record-container">
            @if(session('success'))
                <div class="clinic-alert clinic-alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('status'))
                <div class="clinic-alert clinic-alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('status') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="clinic-alert clinic-alert-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span>{{ session('error') }}</span>
                </div>
            @endif

            <section class="clinic-appointments-card clinic-record-list-card" aria-labelledby="appointments-title">
                <div class="clinic-card-heading">
                    <div>
                        <p class="clinic-eyebrow mb-1">SCHEDULE</p>
                        <h2 id="appointments-title">Appointment list</h2>
                        <p class="clinic-card-description">Requested on shows the original submission time. Scheduled visit shows the appointment time. Times are in {{ config('clinic.timezone') }}.</p>
                    </div>
                    <span class="clinic-count-pill">
                        <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                        {{ $appointments->total() }} {{ \Illuminate\Support\Str::plural('appointment', $appointments->total()) }}
                    </span>
                </div>

                <form method="GET" action="{{ route('appointments.index') }}" class="clinic-list-filters">
                    @if($selectedPatient)
                        <input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
                        <p class="clinic-filter-context">Appointments for <strong>{{ $selectedPatient->name }}</strong></p>
                    @endif
                    <div>
                        <label for="filter-status">Status</label>
                        <select id="filter-status" name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach(['pending', 'confirmed', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" />
                    </div>
                    <div>
                        <label for="filter-date">Appointment date</label>
                        <input id="filter-date" type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="form-control">
                        <x-input-error :messages="$errors->get('date')" />
                    </div>
                    <button type="submit" class="clinic-primary-button">Apply filters</button>
                    <a href="{{ route('appointments.index') }}" class="clinic-action-button clinic-action-view">Clear filters</a>
                </form>

                <div class="clinic-table-wrap">
                    <table id="appointments-table" data-clinic-table data-page-length="10" class="clinic-appointments-table clinic-record-table">
                        <thead>
                            <tr>
                                <th scope="col">Patient</th>
                                <th scope="col">Doctor</th>
                                <th scope="col">Service</th>
                                <th scope="col">Requested on</th>
                                <th scope="col">Scheduled visit</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="clinic-actions-heading">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $appointment)
                                @include('appointments._row', ['appointment' => $appointment])
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="clinic-empty-state">
                                            <span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span>
                                            <h3>No appointments found</h3>
                                            <p>No appointments match this view. Try another date or clear the filters.</p>
                                            @if(auth()->user()->role === 'patient')
                                                <a href="{{ route('appointments.create') }}" class="clinic-primary-button clinic-empty-action">
                                                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Request an appointment
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
                    <div class="clinic-pagination" data-table-fallback>{{ $appointments->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
