<x-app-layout>
    <x-slot name="header">
        <div class="clinic-record-heading">
            <div>
                <p class="clinic-eyebrow mb-1">CLINIC MANAGEMENT</p>
                <h1 class="clinic-page-title">Clinic Services</h1>
            </div>
            <a href="{{ route('services.create') }}" class="clinic-primary-button">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add service
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

            <section class="clinic-appointments-card clinic-record-list-card" aria-labelledby="services-title">
                <div class="clinic-card-heading">
                    <div>
                        <p class="clinic-eyebrow mb-1">TREATMENTS</p>
                        <h2 id="services-title">Service list</h2>
                        <p class="clinic-card-description">Review clinic services, prices, and appointment counts.</p>
                    </div>
                    <span class="clinic-count-pill">
                        <i class="bi bi-heart-pulse" aria-hidden="true"></i>
                        {{ $services->total() }} {{ \Illuminate\Support\Str::plural('service', $services->total()) }}
                    </span>
                </div>

                <div class="clinic-table-wrap">
                    <table class="clinic-appointments-table clinic-record-table">
                        <thead>
                            <tr>
                                <th scope="col">Service</th>
                                <th scope="col">Price</th>
                                <th scope="col">Appointments</th>
                                <th scope="col" class="clinic-actions-heading">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($services as $service)
                                <tr>
                                    <td>
                                        <div class="clinic-person-cell">
                                            <span class="clinic-person-avatar" aria-hidden="true"><i class="bi bi-heart-pulse-fill"></i></span>
                                            <span class="clinic-person-name">{{ $service->name }}</span>
                                        </div>
                                    </td>
                                    <td class="clinic-service-cell">₱{{ number_format((float) $service->price, 2) }}</td>
                                    <td>{{ $service->appointments_count }}</td>
                                    <td>
                                        <div class="clinic-row-actions">
                                            <a href="{{ route('services.show', $service) }}" class="clinic-action-button clinic-action-view">
                                                <i class="bi bi-eye" aria-hidden="true"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="clinic-empty-state">
                                            <span class="clinic-empty-icon" aria-hidden="true"><i class="bi bi-heart-pulse"></i></span>
                                            <h3>No services have been added</h3>
                                            <p>Services offered by your clinic will appear here.</p>
                                            <a href="{{ route('services.create') }}" class="clinic-primary-button clinic-empty-action">
                                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add a service
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($services->hasPages())
                    <div class="clinic-pagination">{{ $services->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
