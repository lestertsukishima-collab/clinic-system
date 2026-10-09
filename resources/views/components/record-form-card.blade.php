@props(['title', 'description', 'icon' => 'bi-clipboard2'])

<section class="clinic-record-page">
    <div class="clinic-record-container clinic-record-container-narrow">
        <section class="clinic-detail-card" aria-label="{{ $title }}">
            <div class="clinic-detail-card-header">
                <div class="clinic-detail-heading-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></div>
                <div class="clinic-detail-heading-copy">
                    <h2>{{ $title }}</h2>
                    <p>{{ $description }}</p>
                </div>
            </div>
            <div class="clinic-form-content">
                {{ $slot }}
            </div>
        </section>
    </div>
</section>
