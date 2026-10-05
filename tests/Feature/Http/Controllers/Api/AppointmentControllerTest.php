<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('returns 401 when the appointment API request is unauthenticated', function () {
    $this->getJson('/api/appointments')->assertUnauthorized();
});

it('issues and accepts a bearer token for protected API routes', function () {
    $user = User::factory()->patient()->create();

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id);

    $accessToken = $response->json('access_token');

    $this->withHeader('Authorization', 'Bearer '.$accessToken)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);

    $this->withHeader('Authorization', 'Bearer '.$accessToken)
        ->deleteJson('/api/v1/auth/token')
        ->assertOk()
        ->assertJsonPath('message', 'Token revoked successfully.');

    Auth::forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$accessToken)
        ->getJson('/api/user')
        ->assertUnauthorized();
});

it('authenticates API requests from the active browser session and clears access on logout', function () {
    $user = User::factory()->patient()->create();

    $this->post('https://localhost/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->withHeader('Origin', 'https://localhost')
        ->getJson('https://localhost/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id);

    $this->deleteJson('https://localhost/api/v1/auth/token')
        ->assertOk()
        ->assertJsonPath('message', 'Session ended successfully.');

    Auth::forgetGuards();

    $this->withHeader('Origin', 'https://localhost')
        ->getJson('https://localhost/api/user')
        ->assertUnauthorized();
});

it('creates an appointment for the authenticated patient and ignores a spoofed patient id', function () {
    $patient = User::factory()->patient()->create();
    $otherPatient = User::factory()->patient()->create();
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();

    $response = $this->actingAs($patient)->postJson('/api/appointments', [
        'patient_id' => $otherPatient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'appointment_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'notes' => 'API booking note.',
        'status' => 'completed',
    ]);

    $response->assertCreated()->assertJsonPath('data.patient.id', $patient->id);
    $this->assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'notes' => 'API booking note.',
        'status' => 'pending',
    ]);
});

it('returns 404 when a patient reads another patient appointment', function () {
    $patient = User::factory()->patient()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($patient)
        ->getJson('/api/appointments/'.$appointment->id)
        ->assertNotFound();
});

it('returns only the signed-in doctor appointments from the API', function () {
    $doctor = Doctor::factory()->create();
    $ownAppointment = Appointment::factory()->for($doctor, 'doctor')->create();
    $otherAppointment = Appointment::factory()->create();

    $this->actingAs($doctor->user)
        ->getJson('/api/appointments')
        ->assertOk()
        ->assertJsonPath('data.0.id', $ownAppointment->id)
        ->assertJsonCount(1, 'data');
});

it('returns 404 and preserves another patient appointment when cancellation is attempted', function () {
    $patient = User::factory()->patient()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($patient)
        ->deleteJson('/api/appointments/'.$appointment->id)
        ->assertNotFound();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'pending',
    ]);
});

it('updates and cancels the patient own pending appointment through the API', function () {
    $patient = User::factory()->patient()->create();
    $appointment = Appointment::factory()->for($patient, 'patient')->create();
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($patient)
        ->patchJson('/api/appointments/'.$appointment->id, [
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'notes' => 'Changed via API.',
        ])
        ->assertOk()
        ->assertJsonPath('data.notes', 'Changed via API.');

    $this->actingAs($patient)
        ->deleteJson('/api/appointments/'.$appointment->id)
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'patient_id' => $patient->id,
        'status' => 'cancelled',
    ]);
});
