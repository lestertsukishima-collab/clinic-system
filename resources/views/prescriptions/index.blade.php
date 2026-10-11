@php($isPatient = auth()->user()->role === 'patient')
<x-app-layout>
    <x-slot name="header">
        <div class="clinic-record-heading">
            <div>
                <p class="clinic-eyebrow mb-1">{{ $isPatient ? 'PATIENT WORKSPACE' : 'CLINIC MANAGEMENT' }}</p>
                <h1 class="clinic-page-title">{{ $isPatient ? 'My prescriptions' : 'Prescriptions' }}</h1>
            </div>
            @unless($isPatient)
                <a href="{{ route('prescriptions.create') }}" class="clinic-primary-button">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Create prescription
                </a>
            @endunless
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
                        <p class="clinic-card-description">{{ $isPatient ? 'Read and print prescriptions from your completed visits. Contact your doctor if you have questions about your prescription.' : 'Review prescriptions associated with clinic appointments.' }}</p>
                    </div>
                    <span class="clinic-count-pill">
                        <i class="bi bi-prescription2" aria-hidden="true"></i>
                        {{ $prescriptions->total() }} {{ \Illuminate\Support\Str::plural('prescription', $prescriptions->total()) }}
                    </span>
                </div>

                <div class="clinic-table-wrap">
                    <table id="prescriptions-table" data-clinic-table data-page-length="10" class="clinic-appointments-table clinic-record-table clinic-prescription-table">
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
                                @include('prescriptions._row', ['prescription' => $prescription])
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="clinic-empty-state">
                                            <span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-prescription2"></i></span>
                                            <h3>No prescriptions recorded</h3>
                                            <p>{{ $isPatient ? 'Prescriptions will appear here after your doctor completes the visit and records a prescription.' : 'Prescriptions linked to appointments will appear here.' }}</p>
                                            @unless($isPatient)
                                                <a href="{{ route('prescriptions.create') }}" class="clinic-primary-button clinic-empty-action">
                                                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Create a prescription
                                                </a>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($prescriptions->hasPages())
                    <div class="clinic-pagination" data-table-fallback>{{ $prescriptions->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
