<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;
use Symfony\Component\Mailer\Exception\TransportException;

it('rejects overlapping doctor bookings without creating an appointment', function (string $endpoint, string $status, string $time) {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $patient = User::factory()->patient()->create();
    $existing = Appointment::factory()->create([
        'appointment_date' => '2026-10-10 01:00:00',
        'status' => $status,
    ]);

    $this->actingAs($patient)->postJson($endpoint, [
        'doctor_id' => $existing->doctor_id,
        'service_id' => $existing->service_id,
        'appointment_date' => $time,
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'appointment_date' => 'This appointment overlaps an existing booking for the doctor or patient. Please choose another time.',
    ]);

    $this->assertDatabaseCount('appointments', 1);
    $this->assertDatabaseHas('appointments', ['id' => $existing->id, 'status' => $status]);
})->with(['/appointments', '/api/appointments'])
    ->with(['pending', 'confirmed'])
    ->with(['2026-10-10T08:45', '2026-10-10T09:00', '2026-10-10T09:15']);

it('rejects overlapping patient bookings with a different doctor', function (string $endpoint) {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $existing = Appointment::factory()->create(['appointment_date' => '2026-10-10 01:00:00']);
    $otherDoctor = Doctor::factory()->create();

    $this->actingAs($existing->patient)->postJson($endpoint, [
        'doctor_id' => $otherDoctor->id,
        'service_id' => $existing->service_id,
        'appointment_date' => '2026-10-10T09:15',
    ])->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseCount('appointments', 1);
})->with(['/appointments', '/api/appointments']);

it('allows adjacent bookings and reuses cancelled times', function (string $endpoint, string $status, string $time) {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $patient = User::factory()->patient()->create();
    $existing = Appointment::factory()->create([
        'appointment_date' => '2026-10-10 01:00:00',
        'status' => $status,
    ]);

    $response = $this->actingAs($patient)->postJson($endpoint, [
        'doctor_id' => $existing->doctor_id,
        'service_id' => $existing->service_id,
        'appointment_date' => $time,
    ]);

    $response->assertSuccessful();
    $this->assertDatabaseCount('appointments', 2);
    $this->assertDatabaseHas('appointments', ['patient_id' => $patient->id, 'status' => 'pending']);
})->with(['/api/appointments'])
    ->with([
        'before adjacent' => ['confirmed', '2026-10-10T08:30'],
        'after adjacent' => ['pending', '2026-10-10T09:30'],
        'cancelled slot' => ['cancelled', '2026-10-10T09:00'],
    ]);

it('preserves the original booking when rescheduling conflicts', function (string $prefix) {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $existing = Appointment::factory()->create(['appointment_date' => '2026-10-10 01:00:00']);
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-11 02:00:00']);

    $this->actingAs($appointment->patient)->putJson($prefix.'/'.$appointment->id, [
        'doctor_id' => $existing->doctor_id,
        'service_id' => $existing->service_id,
        'appointment_date' => '2026-10-10T09:15',
    ])->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'doctor_id' => $appointment->doctor_id,
        'appointment_date' => '2026-10-11 02:00:00',
        'status' => 'pending',
    ]);
})->with(['/appointments', '/api/appointments']);

it('keeps the same booking when editing its notes', function (string $prefix) {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-10 01:00:00']);

    $this->actingAs($appointment->patient)->putJson($prefix.'/'.$appointment->id, [
        'doctor_id' => $appointment->doctor_id,
        'service_id' => $appointment->service_id,
        'appointment_date' => '2026-10-10T09:00',
        'notes' => 'Bring test results.',
    ])->assertValid();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'appointment_date' => '2026-10-10 01:00:00',
        'notes' => 'Bring test results.',
    ]);
    $this->assertDatabaseCount('appointments', 1);
})->with(['/appointments', '/api/appointments']);

it('stores a Philippine local booking in UTC and displays the entered time', function () {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $patient = User::factory()->patient()->create();
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($patient)->post(route('appointments.store'), [
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'appointment_date' => '2026-10-10T09:00',
    ])->assertRedirect();

    $appointment = Appointment::query()->firstOrFail();
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'appointment_date' => '2026-10-10 01:00:00',
        'patient_id' => $patient->id,
    ]);

    $this->actingAs($appointment->patient)->get(route('appointments.edit', $appointment))
        ->assertOk()->assertSee('2026-10-10T09:00');

    $this->get(route('appointments.show', $appointment))->assertOk()->assertSee('9:00 AM');
});

it('rejects a local booking that is already past even when its wall clock time exceeds UTC', function (string $endpoint) {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(2, 0));
    $appointment = Appointment::factory()->create();

    $this->actingAs($appointment->patient)->postJson($endpoint, [
        'doctor_id' => $appointment->doctor_id,
        'service_id' => $appointment->service_id,
        'appointment_date' => '2026-10-09T09:00',
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'appointment_date' => 'The appointment date must be a date after now.',
    ]);

    $this->assertDatabaseCount('appointments', 1);
})->with(['/appointments', '/api/appointments']);

it('normalizes offset timestamps before detecting API conflicts', function () {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $existing = Appointment::factory()->create(['appointment_date' => '2026-10-10 01:00:00']);
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)->postJson('/api/appointments', [
        'doctor_id' => $existing->doctor_id,
        'service_id' => $existing->service_id,
        'appointment_date' => '2026-10-10T01:00:00Z',
    ])->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseCount('appointments', 1);
});

it('preserves clinic history and authentication when a patient or doctor deletes their account', function (string $role) {
    $prescription = Prescription::factory()->create();
    $appointment = $prescription->appointment;
    $user = $role === 'patient' ? $appointment->patient : $appointment->doctor->user;

    $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/profile')->assertSessionHasErrorsIn('userDeletion', [
            'account' => 'Your account has clinic records and cannot be deleted. Please contact the clinic administrator.',
        ]);

    $this->assertAuthenticatedAs($user);
    $this->assertModelExists($user);
    $this->assertModelExists($appointment);
    $this->assertModelExists($prescription);
})->with(['patient', 'doctor']);

it('confirms the appointment and reports a delivery failure without an error page', function () {
    Exceptions::fake();
    $appointment = Appointment::factory()->create();
    $failure = new TransportException('SMTP unavailable');
    Mail::shouldReceive('to')->once()->with($appointment->patient->email)->andReturnSelf();
    Mail::shouldReceive('send')->once()->andThrow($failure);

    $this->actingAs($appointment->doctor->user)->from(route('appointments.show', $appointment))
        ->post(route('appointments.confirm', $appointment))
        ->assertRedirect(route('appointments.show', $appointment))
        ->assertSessionHas('toast.message', 'Appointment confirmed, but the email could not be sent. Please notify the patient directly.');

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
    Exceptions::assertReported(fn (TransportException $reported): bool => $reported === $failure);
});

it('refuses to confirm an expired appointment without sending mail', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create(['appointment_date' => now()->subDay()]);

    $this->actingAs($appointment->doctor->user)->postJson(route('appointments.confirm', $appointment))
        ->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
    Mail::assertNothingSent();
});

it('refuses to confirm a legacy conflicting booking without sending mail', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create();
    Appointment::factory()->for($appointment->doctor, 'doctor')->create([
        'appointment_date' => $appointment->appointment_date,
        'status' => 'confirmed',
    ]);

    $this->actingAs($appointment->doctor->user)->postJson(route('appointments.confirm', $appointment))
        ->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
    Mail::assertNothingSent();
});

it('sends only one email when confirmation is repeated', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create();

    $this->actingAs($appointment->doctor->user)->post(route('appointments.confirm', $appointment))->assertRedirect();
    $this->post(route('appointments.confirm', $appointment))->assertConflict();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
    Mail::assertSentCount(1);
});

it('returns a retry message without writing while another booking request holds the lock', function () {
    $this->freezeTime();
    Sleep::fake();
    Sleep::whenFakingSleep(fn () => $this->travel(1)->seconds());
    $appointment = Appointment::factory()->create();
    $lock = Cache::lock('clinic:appointment-workflows', 30);
    $lock->get();

    try {
        $this->actingAs($appointment->patient)->postJson('/api/appointments', [
            'doctor_id' => $appointment->doctor_id,
            'service_id' => $appointment->service_id,
            'appointment_date' => now()->addDays(5)->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'appointment_date' => 'Another clinic request is being processed. Please try again.',
        ]);

        $this->assertDatabaseCount('appointments', 1);
    } finally {
        $lock->release();
    }
});

it('checks conflicts when an administrator books for a patient', function () {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $admin = User::factory()->admin()->create();
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-10 01:00:00']);
    $otherDoctor = Doctor::factory()->create();

    $this->actingAs($admin)->postJson('/api/appointments', [
        'patient_id' => $appointment->patient_id,
        'doctor_id' => $otherDoctor->id,
        'service_id' => $appointment->service_id,
        'appointment_date' => '2026-10-10T09:00',
    ])->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseCount('appointments', 1);
});

it('refuses confirmation by a different doctor without changing state or sending email', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create();
    $otherDoctor = Doctor::factory()->create();

    $this->actingAs($otherDoctor->user)->post(route('appointments.confirm', $appointment))->assertForbidden();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
    Mail::assertNothingSent();
});

it('keeps cancelled or completed bookings immutable when an administrator edits them', function (string $status) {
    $admin = User::factory()->admin()->create();
    $appointment = Appointment::factory()->create(['status' => $status]);

    $this->actingAs($admin)->putJson('/api/appointments/'.$appointment->id, [
        'doctor_id' => $appointment->doctor_id,
        'service_id' => $appointment->service_id,
        'appointment_date' => now()->addDays(5)->toDateTimeString(),
    ])->assertConflict();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => $status]);
})->with(['cancelled', 'completed']);

it('rejects malformed booking dates without writing an appointment', function (string $endpoint) {
    $appointment = Appointment::factory()->create();

    $this->actingAs($appointment->patient)->postJson($endpoint, [
        'doctor_id' => $appointment->doctor_id,
        'service_id' => $appointment->service_id,
        'appointment_date' => 'not-a-date',
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'appointment_date' => 'The appointment date field must be a valid date.',
    ]);

    $this->assertDatabaseCount('appointments', 1);
})->with(['/appointments', '/api/appointments']);

it('shows the conflict message and preserves local input on the booking form', function () {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-10 01:00:00']);
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)->get(route('appointments.create'))->assertOk();

    $this->actingAs($patient)->from(route('appointments.create'))->post(route('appointments.store'), [
        'doctor_id' => $appointment->doctor_id,
        'service_id' => $appointment->service_id,
        'appointment_date' => '2026-10-10T09:15',
    ])->assertRedirect(route('appointments.create'));

    $this->get(route('appointments.create'))->assertViewHas('errors', fn ($errors): bool => $errors->has('appointment_date'))
        ->assertSee('2026-10-10T09:15')
        ->assertSee('This appointment overlaps an existing booking for the doctor or patient. Please choose another time.');
    $this->assertDatabaseCount('appointments', 1);
});

it('shows a clear error on the appointment page when confirmation conflicts', function () {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(0, 0));
    Mail::fake();
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-10 01:00:00']);
    Appointment::factory()->for($appointment->doctor, 'doctor')->create(['appointment_date' => '2026-10-10 01:00:00']);

    $this->actingAs($appointment->doctor->user)->from(route('appointments.show', $appointment))
        ->post(route('appointments.confirm', $appointment))->assertRedirect(route('appointments.show', $appointment));

    $this->get(route('appointments.show', $appointment))
        ->assertSee('This appointment overlaps an existing booking for the doctor or patient. Please choose another time.');
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
    Mail::assertNothingSent();
});
