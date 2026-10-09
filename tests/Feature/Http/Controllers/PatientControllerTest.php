<?php

use App\Models\Appointment;
use App\Models\User;

it('requires authentication to view the patient directory', function () {
    $this->get(route('patients.index'))->assertRedirect(route('login'));
});

it('forbids non administrators from accessing the patient directory', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('patients.index'))->assertForbidden();
})->with(['patient', 'doctor']);

it('shows patient accounts with appointment counts and history links', function () {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->patient()->create();
    $appointment = Appointment::factory()->for($patient, 'patient')->create();

    $this->actingAs($admin)->get(route('patients.index'))
        ->assertSee($patient->email)
        ->assertSee(route('appointments.index', ['patient_id' => $patient->id]), false)
        ->assertDontSee($appointment->doctor->user->email)
        ->assertViewHas('patients', fn ($patients) => $patients->total() === 1 && $patients->first()->appointments_count === 1);
});

it('searches patient names and emails without returning staff accounts', function (string $search) {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->patient()->create(['name' => 'Directory Match', 'email' => 'patient-match@example.test']);
    User::factory()->doctor()->create(['name' => 'Directory Match', 'email' => 'staff-patient-match@example.test']);
    User::factory()->patient()->create(['name' => 'Different Person', 'email' => 'different@example.test']);

    $this->actingAs($admin)->get(route('patients.index', ['search' => $search]))
        ->assertViewHas('patients', fn ($patients) => $patients->modelKeys() === [$patient->id]);
})->with(['Directory Match', 'patient-match']);

it('renders escaped patient information and an empty search state', function () {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->patient()->create(['name' => '<script>alert(1)</script>']);

    $this->actingAs($admin)->get(route('patients.index'))
        ->assertSee($patient->name)
        ->assertDontSee($patient->name, false);
    $this->get(route('patients.index', ['search' => 'no-match']))->assertSee('No patients found');
});

it('rejects invalid patient searches', function (mixed $search) {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('patients.index', ['search' => $search]))
        ->assertUnprocessable()->assertJsonValidationErrors('search');
})->with(['array' => [['invalid']], 'too long' => [str_repeat('a', 101)]]);

it('preserves the search when paging through patients', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->patient()->count(16)->create(['name' => 'Matched Patient']);

    $this->actingAs($admin)->get(route('patients.index', ['search' => 'Matched', 'page' => 2]))
        ->assertViewHas('patients', fn ($patients) => $patients->count() === 1 && str_contains($patients->previousPageUrl(), 'search=Matched'));
});
