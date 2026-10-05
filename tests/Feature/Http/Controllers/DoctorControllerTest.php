<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('renders every doctor management page for administrators', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin)->get(route('doctors.index'))->assertOk();
    $this->actingAs($admin)->get(route('doctors.create'))->assertOk();
    $this->actingAs($admin)->get(route('doctors.show', $doctor))->assertOk();
    $this->actingAs($admin)->get(route('doctors.edit', $doctor))->assertOk();
});

it('creates a doctor profile and login account together', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('doctors.store'), [
        'name' => 'Dr. Ada Santos',
        'email' => 'ada.santos@example.test',
        'password' => 'secure-password',
        'password_confirmation' => 'secure-password',
        'specialization' => 'Pediatrics',
        'phone' => '09171234567',
    ]);

    $doctor = Doctor::query()->whereHas('user', fn ($query) => $query->where('email', 'ada.santos@example.test'))->firstOrFail();

    $response->assertRedirect(route('doctors.show', $doctor));
    $this->assertDatabaseHas('users', [
        'id' => $doctor->user_id,
        'role' => 'doctor',
    ]);
    expect(Hash::check('secure-password', $doctor->user->password))->toBeTrue();
});

it('updates doctor details and account information', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin)
        ->put(route('doctors.update', $doctor), [
            'name' => 'Dr. Updated Name',
            'email' => $doctor->user->email,
            'specialization' => 'Family Medicine',
            'phone' => '09170000000',
        ])
        ->assertRedirect(route('doctors.show', $doctor));

    $this->assertDatabaseHas('users', [
        'id' => $doctor->user_id,
        'name' => 'Dr. Updated Name',
    ]);
    $this->assertDatabaseHas('doctors', [
        'id' => $doctor->id,
        'specialization' => 'Family Medicine',
        'phone' => '09170000000',
    ]);
});

it('keeps a doctor and account when clinic appointments reference them', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->for($doctor, 'doctor')->create();

    $this->actingAs($admin)
        ->from(route('doctors.show', $doctor))
        ->delete(route('doctors.destroy', $doctor))
        ->assertRedirect(route('doctors.show', $doctor))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('doctors', ['id' => $doctor->id]);
    $this->assertDatabaseHas('users', ['id' => $doctor->user_id]);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
});

it('deletes an unused doctor profile and its doctor account', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $userId = $doctor->user_id;

    $this->actingAs($admin)
        ->delete(route('doctors.destroy', $doctor))
        ->assertRedirect(route('doctors.index'));

    $this->assertDatabaseMissing('doctors', ['id' => $doctor->id]);
    $this->assertDatabaseMissing('users', ['id' => $userId]);
});

it('forbids patients from managing doctor records', function () {
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)
        ->get(route('doctors.index'))
        ->assertForbidden();
});
