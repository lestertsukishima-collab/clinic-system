<x-app-layout>
    <x-slot name="header">
        <div class="clinic-record-heading">
            <div>
                <p class="clinic-eyebrow mb-1">CLINIC MANAGEMENT</p>
                <h1 class="clinic-page-title">Doctors</h1>
            </div>
            <a href="{{ route('doctors.create') }}" class="clinic-primary-button">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add doctor
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
            @if(session('error'))
                <div class="clinic-alert clinic-alert-error" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span>{{ session('error') }}</span>
                </div>
            @endif

            <section class="clinic-appointments-card clinic-record-list-card" aria-labelledby="doctors-title">
                <div class="clinic-card-heading">
                    <div>
                        <p class="clinic-eyebrow mb-1">CARE TEAM</p>
                        <h2 id="doctors-title">Doctor list</h2>
                        <p class="clinic-card-description">View doctors and their clinic appointments.</p>
                    </div>
                    <span class="clinic-count-pill">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        {{ $doctors->total() }} {{ \Illuminate\Support\Str::plural('doctor', $doctors->total()) }}
                    </span>
                </div>

                <div class="clinic-table-wrap">
                    <table id="doctors-table" data-clinic-table data-page-length="15" class="clinic-appointments-table clinic-record-table">
                        <thead>
                            <tr>
                                <th scope="col">Doctor</th>
                                <th scope="col">Email</th>
                                <th scope="col">Specialization</th>
                                <th scope="col">Appointments</th>
                                <th scope="col" class="clinic-actions-heading">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($doctors as $doctor)
                                @include('doctors._row', ['doctor' => $doctor])
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="clinic-empty-state">
                                            <span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                                            <h3>No doctors have been added</h3>
                                            <p>Doctors added to your clinic will appear here.</p>
                                            <a href="{{ route('doctors.create') }}" class="clinic-primary-button clinic-empty-action">
                                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add a doctor
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($doctors->hasPages())
                    <div class="clinic-pagination" data-table-fallback>{{ $doctors->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
