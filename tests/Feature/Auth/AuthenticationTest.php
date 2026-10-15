<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertSee(route('register'), false)
        ->assertSee('Clinic home')
        ->assertHeader('Cache-Control', 'no-store, private');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('logout returns every role to login and blocks dashboard access until signing in again', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    $response = $this->withSession(['private_session_data' => 'Clear on logout'])->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('login'))
        ->assertSessionMissing('private_session_data')
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->get(route('appointments.index'))->assertRedirect(route('login'));
    $this->get('/')->assertSee(route('login'), false)
        ->assertSee(route('register'), false)
        ->assertDontSee('Open dashboard');
    $this->get(route('register'))->assertSee('Clinic home');

    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    $this->assertGuest();
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertOk();
})->with(['patient', 'doctor', 'admin']);
