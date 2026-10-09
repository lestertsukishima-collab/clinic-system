<?php

use App\Models\User;

it('keeps passwords masked by default and preserves form input attributes', function () {
    $view = $this->blade('<x-password-input id="temporary_password" name="password" required minlength="8" autocomplete="new-password" />');

    $view->assertSee('type="password"', false)
        ->assertSee('name="password"', false)
        ->assertSee('required', false)
        ->assertSee('minlength="8"', false)
        ->assertSee('autocomplete="new-password"', false)
        ->assertSee('type="button"', false)
        ->assertSee('aria-controls="temporary_password"', false)
        ->assertSee('aria-label="Show password"', false)
        ->assertSee('aria-pressed="false"', false);
});

it('disables both the password input and its visibility button when disabled', function () {
    $view = $this->blade('<x-password-input id="password" name="password" disabled />');

    expect(substr_count((string) $view, 'disabled'))->toBe(2);
});

it('offers a visibility control for every password field on related pages', function (string $path, ?string $role, array $passwordIds) {
    if ($role !== null) {
        $this->actingAs(User::factory()->create(['role' => $role]));
    }

    $response = $this->get($path);

    foreach ($passwordIds as $passwordId) {
        $response->assertSee('aria-controls="'.$passwordId.'"', false);
    }
})->with([
    'login' => ['/login', null, ['password']],
    'registration' => ['/register', null, ['password', 'password_confirmation']],
    'reset password' => ['/reset-password/test-token', null, ['password', 'password_confirmation']],
    'confirm password' => ['/confirm-password', 'patient', ['password']],
    'create doctor' => ['/doctors/create', 'admin', ['password', 'password_confirmation']],
    'patient profile' => ['/profile', 'patient', ['update_password_current_password', 'update_password_password', 'update_password_password_confirmation', 'password']],
    'doctor profile' => ['/profile', 'doctor', ['update_password_current_password', 'update_password_password', 'update_password_password_confirmation', 'password']],
    'admin profile' => ['/profile', 'admin', ['update_password_current_password', 'update_password_password', 'update_password_password_confirmation', 'password']],
]);
