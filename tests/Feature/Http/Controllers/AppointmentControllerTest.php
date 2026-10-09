<?php

use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('renders the appointment booking, list, detail, and edit pages', function () {
    $patient = User::factory()->patient()->create();
    $appointment = Appointment::factory()->for($patient, 'patient')->create();

    $this->actingAs($patient)->get(route('appointments.index'))->assertOk();
    $this->actingAs($patient)->get(route('appointments.create'))->assertOk();
    $this->actingAs($patient)->get(route('appointments.show', $appointment))->assertOk();
    $this->actingAs($patient)->get(route('appointments.edit', $appointment))->assertOk();
});

it('creates an appointment for the signed-in patient and saves notes', function () {
    $patient = User::factory()->patient()->create();
    $otherPatient = User::factory()->patient()->create();
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();

    $response = $this->actingAs($patient)->post(route('appointments.store'), [
        'patient_id' => $otherPatient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'appointment_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'notes' => 'Bring previous test results.',
    ]);

    $appointment = Appointment::query()->firstOrFail();

    $response->assertRedirect(route('appointments.show', $appointment));
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'notes' => 'Bring previous test results.',
        'status' => 'pending',
    ]);
});

it('shows a patient only their own appointment list', function () {
    $patient = User::factory()->patient()->create();
    $otherAppointment = Appointment::factory()->create();

    $response = $this->actingAs($patient)->get(route('appointments.index'));

    $response->assertOk()->assertDontSee($otherAppointment->patient->name);
    $this->assertDatabaseHas('appointments', ['id' => $otherAppointment->id]);
});

it('hides another patient appointment when requested directly', function () {
    $patient = User::factory()->patient()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($patient)
        ->get(route('appointments.show', $appointment))
        ->assertNotFound();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
});

it('lets the assigned doctor see their appointments on the dashboard', function () {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->for($doctor, 'doctor')->create();

    $this->actingAs($doctor->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee($appointment->patient->name)
        ->assertSee('Pending')
        ->assertSee(route('appointments.confirm', $appointment), false)
        ->assertDontSee('delete-btn');
});

it('cancels a pending appointment without deleting its history', function () {
    $patient = User::factory()->patient()->create();
    $appointment = Appointment::factory()->for($patient, 'patient')->create();

    $this->actingAs($patient)
        ->delete(route('appointments.destroy', $appointment))
        ->assertRedirect(route('appointments.index'));

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'cancelled',
    ]);
});

it('updates a pending appointment and keeps its patient assignment', function () {
    $patient = User::factory()->patient()->create();
    $appointment = Appointment::factory()->for($patient, 'patient')->create();
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();

    $this->actingAs($patient)
        ->put(route('appointments.update', $appointment), [
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
            'notes' => 'Updated notes.',
            'patient_id' => User::factory()->patient()->create()->id,
        ])
        ->assertRedirect(route('appointments.show', $appointment));

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'service_id' => $service->id,
        'notes' => 'Updated notes.',
    ]);
});

it('confirms an assigned doctor appointment and sends a confirmation email', function () {
    Mail::fake();
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->for($doctor, 'doctor')->create();

    $this->actingAs($doctor->user)
        ->from(route('appointments.show', $appointment))
        ->post(route('appointments.confirm', $appointment))
        ->assertRedirect(route('appointments.show', $appointment));

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'confirmed',
    ]);
    Mail::assertSent(AppointmentConfirmed::class);
});

it('creates valid linked records through the domain factories and relationships', function () {
    $prescription = Prescription::factory()->create();
    $appointment = $prescription->appointment;

    expect($appointment->patient->role)->toBe('patient')
        ->and($appointment->doctor->user->role)->toBe('doctor')
        ->and($appointment->service)->toBeInstanceOf(Service::class)
        ->and($appointment->prescriptions)->toHaveCount(1)
        ->and($appointment->patient->patientPrescriptions)->toHaveCount(1);
});

it('filters clinic appointments by status and patient for administrators', function () {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->patient()->create();
    $pending = Appointment::factory()->for($patient, 'patient')->create();
    Appointment::factory()->for($patient, 'patient')->create(['status' => 'confirmed']);
    Appointment::factory()->create();

    $this->actingAs($admin)->get(route('appointments.index', ['status' => 'pending', 'patient_id' => $patient->id]))
        ->assertSee('Appointments for')
        ->assertViewHas('appointments', fn ($appointments) => $appointments->modelKeys() === [$pending->id]);
});

it('filters dates by clinic day boundaries and orders appointments earliest first', function () {
    config(['clinic.timezone' => 'Asia/Manila']);
    $admin = User::factory()->admin()->create();
    $appointments = Appointment::factory()->count(4)->sequence(
        ['appointment_date' => '2026-10-09 15:59:59'],
        ['appointment_date' => '2026-10-08 16:00:00'],
        ['appointment_date' => '2026-10-08 15:59:59'],
        ['appointment_date' => '2026-10-09 16:00:00'],
    )->create();

    $this->actingAs($admin)->get(route('appointments.index', ['date' => '2026-10-09']))
        ->assertViewHas('appointments', fn ($results) => $results->modelKeys() === [$appointments[1]->id, $appointments[0]->id]);
});

it('keeps role scoping when a non administrator supplies a patient filter', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $other = Appointment::factory()->create();

    $this->actingAs($user)->get(route('appointments.index', ['patient_id' => $other->patient_id, 'status' => 'pending']))
        ->assertViewHas('appointments', fn ($appointments) => $appointments->total() === 0)
        ->assertDontSee($other->patient->name);
})->with(['patient', 'doctor']);

it('rejects invalid appointment filters', function (string $field, mixed $value) {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('appointments.index', [$field => $value]))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'unknown status' => ['status', 'unknown'],
    'invalid date' => ['date', '2026-02-30'],
    'invalid patient' => ['patient_id', 'invalid'],
    'negative patient' => ['patient_id', -1],
]);

it('does not accept a staff account as a patient filter', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('appointments.index', ['patient_id' => $admin->id]))->assertNotFound();
});

it('preserves appointment filters across pages', function () {
    $admin = User::factory()->admin()->create();
    $patient = User::factory()->patient()->create();
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    Appointment::factory()->for($patient, 'patient')->recycle([$doctor, $service])->count(11)->create();

    $this->actingAs($admin)->get(route('appointments.index', ['status' => 'pending', 'patient_id' => $patient->id, 'page' => 2]))
        ->assertViewHas('appointments', fn ($appointments) => $appointments->count() === 1
            && str_contains($appointments->previousPageUrl(), 'status=pending')
            && str_contains($appointments->previousPageUrl(), 'patient_id='.$patient->id));
});

it('connects an assigned doctors visit to prescribing and completion', function () {
    $this->freezeTime();
    $appointment = Appointment::factory()->create(['status' => 'confirmed', 'appointment_date' => now()->subHour()]);
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create();

    $this->actingAs($appointment->doctor->user)->get(route('appointments.show', $appointment))
        ->assertSee(route('appointments.complete', $appointment), false)
        ->assertSee(route('prescriptions.create', ['appointment_id' => $appointment->id]), false)
        ->assertSee(route('prescriptions.show', $prescription), false);
});

it('does not offer visit completion before the scheduled start time', function () {
    $this->freezeTime();
    $appointment = Appointment::factory()->create(['status' => 'confirmed', 'appointment_date' => now()->addHour()]);

    $this->actingAs($appointment->doctor->user)->get(route('appointments.show', $appointment))
        ->assertDontSee(route('appointments.complete', $appointment), false);
});

it('does not show clinician actions on the patient visit page', function () {
    $this->freezeTime();
    $appointment = Appointment::factory()->create(['status' => 'confirmed', 'appointment_date' => now()->subHour()]);

    $this->actingAs($appointment->patient)->get(route('appointments.show', $appointment))
        ->assertDontSee(route('appointments.complete', $appointment), false)
        ->assertDontSee('Write prescription')
        ->assertDontSee('Prescriptions for this visit');
});

it('links patients to prescriptions from their completed visits', function () {
    $appointment = Appointment::factory()->create(['status' => 'completed']);
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create();

    $this->actingAs($appointment->patient)->get(route('appointments.show', $appointment))
        ->assertSee('Your prescriptions')
        ->assertSee(route('patient-prescriptions.show', $prescription), false)
        ->assertDontSee('Write prescription');
});

it('does not show prescriptions to patients before the visit is completed', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create();

    $this->actingAs($appointment->patient)->get(route('appointments.show', $appointment))
        ->assertSee('Contact the clinic if you need to change or cancel this visit.')
        ->assertDontSee(route('patient-prescriptions.show', $prescription), false);
});

it('offers the doctor a confirmation action before the visit details for every pending request', function (string $scheduledDate, string $button) {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(2, 0));
    $appointment = Appointment::factory()->create(['appointment_date' => $scheduledDate]);

    $this->actingAs($appointment->doctor->user)->get(route('appointments.show', $appointment))
        ->assertSeeInOrder(['Confirm this request', $button, 'Patient', 'Notes'])
        ->assertSee(route('appointments.confirm', $appointment), false);

    $this->actingAs($appointment->patient)->get(route('appointments.show', $appointment))
        ->assertDontSee('Confirm this request')
        ->assertDontSee(route('appointments.confirm', $appointment), false);
})->with([
    'upcoming request' => ['2026-10-10 01:00:00', 'Confirm appointment'],
    'expired request' => ['2026-09-30 13:51:00', 'Reschedule and confirm'],
]);

it('reschedules and confirms an expired request in clinic time while preserving its original submission', function (string $role) {
    config(['clinic.timezone' => 'Asia/Manila']);
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(2, 0));
    Mail::fake();
    $appointment = Appointment::factory()->create([
        'appointment_date' => '2026-09-30 13:51:00',
        'created_at' => '2026-09-30 13:51:56',
        'notes' => 'Bring test results.',
    ]);
    $user = $role === 'doctor' ? $appointment->doctor->user : User::factory()->admin()->create();

    $this->actingAs($user)->from(route('appointments.show', $appointment))
        ->post(route('appointments.confirm', $appointment), [
            'appointment_date' => '2026-10-10T09:00',
            'patient_id' => 0,
            'doctor_id' => 0,
            'service_id' => 0,
            'created_at' => '2026-10-10 01:00:00',
        ])->assertRedirect(route('appointments.show', $appointment))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'confirmed',
        'appointment_date' => '2026-10-10 01:00:00',
        'created_at' => '2026-09-30 13:51:56',
        'doctor_id' => $appointment->doctor_id,
        'patient_id' => $appointment->patient_id,
        'service_id' => $appointment->service_id,
        'notes' => 'Bring test results.',
    ]);
    Mail::assertSent(AppointmentConfirmed::class, fn (AppointmentConfirmed $mail): bool => $mail->hasTo($appointment->patient->email)
        && str_contains($mail->render(), 'Oct 10, 2026 at 9:00 AM')
    );
    $this->get(route('appointments.show', $appointment))
        ->assertSee('9:00 AM')->assertSee('9:51:56 PM')->assertDontSee('Reschedule and confirm');
})->with(['doctor', 'admin']);

it('rejects invalid replacement times without changing the request or sending mail', function (mixed $date) {
    config(['clinic.timezone' => 'Asia/Manila']);
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(2, 0));
    Mail::fake();
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-09-30 13:51:00']);

    $this->actingAs($appointment->doctor->user)->postJson(route('appointments.confirm', $appointment), [
        'appointment_date' => $date,
    ])->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'pending',
        'appointment_date' => '2026-09-30 13:51:00',
    ]);
    Mail::assertNothingSent();
})->with(['empty' => [''], 'malformed' => ['not-a-date'], 'invalid day' => ['2026-02-30T09:00'], 'non scalar' => [[]], 'past in Manila' => ['2026-10-09T09:00']]);

it('preserves the expired request and entered time when rescheduling conflicts with the doctor or patient', function (string $conflict) {
    $this->travelTo(now()->setDate(2026, 10, 9)->setTime(2, 0));
    Mail::fake();
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-09-30 13:51:00']);
    Appointment::factory()->create([
        $conflict === 'doctor' ? 'doctor_id' : 'patient_id' => $conflict === 'doctor' ? $appointment->doctor_id : $appointment->patient_id,
        'appointment_date' => '2026-10-10 01:15:00',
        'status' => 'confirmed',
    ]);

    $this->actingAs($appointment->doctor->user)->from(route('appointments.show', $appointment))
        ->post(route('appointments.confirm', $appointment), ['appointment_date' => '2026-10-10T09:00'])
        ->assertRedirect(route('appointments.show', $appointment));

    $this->get(route('appointments.show', $appointment))
        ->assertSee('2026-10-10T09:00')
        ->assertSee('This appointment overlaps an existing booking for the doctor or patient. Please choose another time.');
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'pending',
        'appointment_date' => '2026-09-30 13:51:00',
    ]);
    Mail::assertNothingSent();
})->with(['doctor', 'patient']);

it('refuses rescheduling and confirmation by a patient or a different doctor', function (string $role) {
    $this->freezeTime();
    Mail::fake();
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-09-30 13:51:00']);
    $user = $role === 'patient' ? $appointment->patient : Doctor::factory()->create()->user;

    $this->actingAs($user)->postJson(route('appointments.confirm', $appointment), [
        'appointment_date' => now(config('clinic.timezone'))->addDays(2)->format('Y-m-d\TH:i'),
    ])->assertForbidden();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending', 'appointment_date' => '2026-09-30 13:51:00']);
    Mail::assertNothingSent();
})->with(['patient', 'other doctor']);

it('does not reschedule a request that has already left pending status', function (string $status) {
    $this->freezeTime();
    Mail::fake();
    $appointment = Appointment::factory()->create(['status' => $status, 'appointment_date' => '2026-09-30 13:51:00']);

    $this->actingAs($appointment->doctor->user)->postJson(route('appointments.confirm', $appointment), [
        'appointment_date' => now(config('clinic.timezone'))->addDays(2)->format('Y-m-d\TH:i'),
    ])->assertConflict();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => $status, 'appointment_date' => '2026-09-30 13:51:00']);
    Mail::assertNothingSent();
})->with(['confirmed', 'cancelled', 'completed']);
