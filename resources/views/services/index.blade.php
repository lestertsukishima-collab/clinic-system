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
                    <table id="services-table" data-clinic-table data-page-length="15" class="clinic-appointments-table clinic-record-table">
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
                                @include('services._row', ['service' => $service])
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
                    <div class="clinic-pagination" data-table-fallback>{{ $services->links() }}</div>
                @endif
            </section>
        </div>
    </section>
</x-app-layout>
