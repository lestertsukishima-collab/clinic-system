<x-app-layout>
    <x-slot name="header">
        <x-record-heading eyebrow="ADMINISTRATION" title="Patient directory" :back-url="route('dashboard')" back-label="Back to overview" />
    </x-slot>

    <section class="clinic-record-page">
        <div class="clinic-record-container">
            <section class="clinic-appointments-card clinic-record-list-card" aria-labelledby="patients-title">
                <div class="clinic-card-heading">
                    <div>
                        <p class="clinic-eyebrow mb-1">PATIENT ACCOUNTS</p>
                        <h2 id="patients-title">Registered patients</h2>
                        <p class="clinic-card-description">Find contact details and review each patient's appointment history.</p>
                    </div>
                    <span class="clinic-count-pill">{{ $patients->total() }} {{ \Illuminate\Support\Str::plural('patient', $patients->total()) }}</span>
                </div>
                <form method="GET" action="{{ route('patients.index') }}" class="clinic-list-filters">
                    <div>
                        <label for="patient-search">Search patients</label>
                        <input id="patient-search" type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Name or email address" class="form-control">
                        <x-input-error :messages="$errors->get('search')" />
                    </div>
                    <button type="submit" class="clinic-primary-button"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
                    <a href="{{ route('patients.index') }}" class="clinic-action-button clinic-action-view">Clear search</a>
                </form>
                <div class="clinic-table-wrap">
                    <table class="clinic-appointments-table clinic-record-table">
                        <thead>
                            <tr>
                                <th scope="col">Patient</th>
                                <th scope="col">Email</th>
                                <th scope="col">Registered</th>
                                <th scope="col">Appointments</th>
                                <th scope="col" class="clinic-actions-heading">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($patients as $patient)
                                <tr>
                                    <td><div class="clinic-person-cell"><span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span><span class="clinic-person-name">{{ $patient->name }}</span></div></td>
                                    <td>{{ $patient->email }}</td>
                                    <td>{{ $patient->created_at->timezone(config('clinic.timezone'))->format('M d, Y') }}</td>
                                    <td>{{ $patient->appointments_count }}</td>
                                    <td><div class="clinic-row-actions"><a href="{{ route('appointments.index', ['patient_id' => $patient->id]) }}" class="clinic-action-button clinic-action-view" aria-label="View appointments for {{ $patient->name }}">Appointments <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="clinic-empty-state"><span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-people"></i></span><h3>No patients found</h3><p>{{ $search !== '' ? 'Try another name or email address, or clear your search.' : 'Patient accounts will appear here after registration.' }}</p></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($patients->hasPages())
                    <div class="clinic-pagination">{{ $patients->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
