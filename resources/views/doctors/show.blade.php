<x-app-layout>
    <x-slot name="header">
        <x-record-heading title="Doctor Details" eyebrow="CARE TEAM" :back-url="route('doctors.index')" back-label="Back to doctors" />
    </x-slot>

    <section class="clinic-record-page">
        <div class="clinic-record-container clinic-record-container-narrow">
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

            <article class="clinic-detail-card">
                <div class="clinic-detail-card-header">
                    <div class="clinic-detail-heading-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></div>
                    <div class="clinic-detail-heading-copy">
                        <p class="clinic-eyebrow mb-1">DOCTOR PROFILE</p>
                        <h2>{{ $doctor->display_name }}</h2>
                        <p>{{ $doctor->specialization }}</p>
                    </div>
                </div>

                <div class="clinic-detail-grid">
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-envelope" aria-hidden="true"></i> Email</span>
                        <strong>{{ $doctor->user->email }}</strong>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-telephone" aria-hidden="true"></i> Phone</span>
                        <strong>{{ $doctor->phone ?: 'Not provided' }}</strong>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-heart-pulse" aria-hidden="true"></i> Specialization</span>
                        <strong>{{ $doctor->specialization }}</strong>
                    </div>
                    <div class="clinic-detail-item">
                        <span class="clinic-detail-label"><i class="bi bi-calendar2-week" aria-hidden="true"></i> Appointments</span>
                        <strong>{{ $doctor->appointments_count }}</strong>
                    </div>
                </div>

                <div class="clinic-detail-actions">
                    <a href="{{ route('doctors.edit', $doctor) }}" class="clinic-primary-button">
                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit doctor
                    </a>
                    <form method="POST" action="{{ route('doctors.destroy', $doctor) }}" onsubmit="return confirm('Delete this doctor account?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="clinic-action-button clinic-action-cancel">
                            <i class="bi bi-trash3" aria-hidden="true"></i> Delete
                        </button>
                    </form>
                </div>
            </article>
        </div>
    </section>
</x-app-layout>
