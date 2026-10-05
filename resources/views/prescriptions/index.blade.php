<x-app-layout>
    <x-slot name="header">
        <div class="clinic-record-heading">
            <div>
                <p class="clinic-eyebrow mb-1">CLINIC MANAGEMENT</p>
                <h1 class="clinic-page-title">Prescriptions</h1>
            </div>
            <a href="{{ route('prescriptions.create') }}" class="clinic-primary-button">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Create prescription
            </a>
        </div>
    </x-slot>

    <section class="clinic-record-page">
        <div class="clinic-record-container">
            @if(session('success'))
                <div class="clinic-alert clinic-alert-success" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') }}</span>
                </div>
            @endif

            <section class="clinic-appointments-card clinic-record-list-card" aria-labelledby="prescriptions-title">
                <div class="clinic-card-heading">
                    <div>
                        <p class="clinic-eyebrow mb-1">TREATMENT RECORDS</p>
                        <h2 id="prescriptions-title">Prescription list</h2>
                        <p class="clinic-card-description">Review prescriptions associated with clinic appointments.</p>
                    </div>
                    <span class="clinic-count-pill">
                        <i class="bi bi-prescription2" aria-hidden="true"></i>
                        {{ $prescriptions->total() }} {{ \Illuminate\Support\Str::plural('prescription', $prescriptions->total()) }}
                    </span>
                </div>

                <div class="clinic-table-wrap">
                    <table class="clinic-appointments-table clinic-record-table clinic-prescription-table">
                        <thead>
                            <tr>
                                <th scope="col">Patient</th>
                                <th scope="col">Doctor</th>
                                <th scope="col">Appointment</th>
                                <th scope="col">Diagnosis</th>
                                <th scope="col" class="clinic-actions-heading">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($prescriptions as $prescription)
                                <tr>
                                    <td>
                                        <div class="clinic-person-cell">
                                            <span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                                            <span class="clinic-person-name">{{ $prescription->appointment->patient->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $prescription->appointment->doctor->display_name }}</td>
                                    <td>
                                        <div class="clinic-date-cell">
                                            <span>{{ $prescription->appointment->appointment_date->format('M d, Y') }}</span>
                                            <small>{{ $prescription->appointment->appointment_date->format('g:i A') }}</small>
                                        </div>
                                    </td>
                                    <td><span class="clinic-diagnosis-preview">{{ \Illuminate\Support\Str::limit($prescription->diagnosis, 68) }}</span></td>
                                    <td>
                                        <div class="clinic-row-actions">
                                            <a href="{{ route('prescriptions.show', $prescription) }}" class="clinic-action-button clinic-action-view">
                                                <i class="bi bi-eye" aria-hidden="true"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="clinic-empty-state">
                                            <span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-prescription2"></i></span>
                                            <h3>No prescriptions recorded</h3>
                                            <p>Prescriptions linked to appointments will appear here.</p>
                                            <a href="{{ route('prescriptions.create') }}" class="clinic-primary-button clinic-empty-action">
                                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Create a prescription
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($prescriptions->hasPages())
                    <div class="clinic-pagination">{{ $prescriptions->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
