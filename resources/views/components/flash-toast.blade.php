@php
    $toast = session('toast');

    if (!$toast && request()->routeIs('login') && $errors->any()) {
        $toast = [
            'type' => 'error',
            'message' => $errors->first(),
        ];
    }

    $toastType = $toast['type'] ?? 'success';
    $toastMessage = $toast['message'] ?? '';
@endphp

@if($toastMessage !== '')
    <div
        x-data="{ visible: true }"
        x-init="window.setTimeout(() => visible = false, 6000)"
        x-show="visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="clinic-toast clinic-toast-{{ $toastType }}"
        role="{{ $toastType === 'error' ? 'alert' : 'status' }}"
        aria-live="{{ $toastType === 'error' ? 'assertive' : 'polite' }}"
    >
        <i class="bi {{ $toastType === 'error' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' }}" aria-hidden="true"></i>
        <span>{{ $toastMessage }}</span>
        <button type="button" class="clinic-toast-dismiss" aria-label="Dismiss notification" @click="visible = false">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
@endif
