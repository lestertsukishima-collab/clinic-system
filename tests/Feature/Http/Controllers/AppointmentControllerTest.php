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
