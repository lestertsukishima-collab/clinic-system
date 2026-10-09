@props(['title', 'eyebrow', 'backUrl', 'backLabel'])

<div class="clinic-record-heading">
    <div>
        <p class="clinic-eyebrow mb-1">{{ $eyebrow }}</p>
        <h1 class="clinic-page-title">{{ $title }}</h1>
    </div>
    <a href="{{ $backUrl }}" class="clinic-back-link">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> {{ $backLabel }}
    </a>
</div>
