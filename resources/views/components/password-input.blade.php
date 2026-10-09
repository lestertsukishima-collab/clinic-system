@props(['disabled' => false])

<div {{ $attributes->only('class')->merge(['class' => 'clinic-password-field']) }} x-data="{ passwordVisible: false }" @keydown.escape="passwordVisible = false">
    <x-text-input
        :disabled="$disabled"
        {{ $attributes->except(['class', 'type']) }}
        type="password"
        class="clinic-password-input"
        x-bind:type="passwordVisible ? 'text' : 'password'"
    />
    <button
        x-cloak
        type="button"
        class="clinic-password-toggle"
        @disabled($disabled)
        @click="passwordVisible = !passwordVisible"
        aria-controls="{{ $attributes->get('id') }}"
        aria-label="{{ __('Show password') }}"
        :aria-label="passwordVisible ? @js(__('Hide password')) : @js(__('Show password'))"
        aria-pressed="false"
        :aria-pressed="passwordVisible"
    >
        <i x-show="!passwordVisible" class="bi bi-eye" aria-hidden="true"></i>
        <i x-cloak x-show="passwordVisible" class="bi bi-eye-slash" aria-hidden="true"></i>
    </button>
</div>
