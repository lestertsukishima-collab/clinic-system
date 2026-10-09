<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\User;

it('renders every prescription management page for the assigned doctor', function () {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->for($doctor, 'doctor')->create();
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create();

    $this->actingAs($doctor->user)->get(route('prescriptions.index'))->assertOk();
    $this->actingAs($doctor->user)->get(route('prescriptions.create'))->assertOk();
    $this->actingAs($doctor->user)->get(route('prescriptions.show', $prescription))->assertOk();
    $this->actingAs($doctor->user)->get(route('prescriptions.edit', $prescription))->assertOk();
});

it('creates a prescription using the existing prescription schema', function () {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->for($doctor, 'doctor')->create();

    $response = $this->actingAs($doctor->user)->post(route('prescriptions.store'), [
        'appointment_id' => $appointment->id,
        'diagnosis' => 'Seasonal allergies',
        'medicines' => 'Cetirizine 10 mg once daily.',
        'instructions' => 'Take with water.',
    ]);

    $prescription = Prescription::query()->firstOrFail();

    $response->assertRedirect(route('prescriptions.show', $prescription));
    $this->assertDatabaseHas('prescriptions', [
        'id' => $prescription->id,
        'appointment_id' => $appointment->id,
        'diagnosis' => 'Seasonal allergies',
        'medicines' => 'Cetirizine 10 mg once daily.',
        'instructions' => 'Take with water.',
    ]);
});

it('updates and deletes a prescription for the assigned doctor', function () {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->for($doctor, 'doctor')->create();
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create();
    $updatedAppointment = Appointment::factory()->for($doctor, 'doctor')->create();

    $this->actingAs($doctor->user)
        ->put(route('prescriptions.update', $prescription), [
            'appointment_id' => $updatedAppointment->id,
            'diagnosis' => 'Updated diagnosis',
            'medicines' => 'Updated medicine',
            'instructions' => 'Follow up in one week.',
        ])
        ->assertRedirect(route('prescriptions.show', $prescription));

    $this->assertDatabaseHas('prescriptions', [
        'id' => $prescription->id,
        'appointment_id' => $updatedAppointment->id,
        'diagnosis' => 'Updated diagnosis',
        'medicines' => 'Updated medicine',
    ]);

    $this->actingAs($doctor->user)
        ->delete(route('prescriptions.destroy', $prescription))
        ->assertRedirect(route('prescriptions.index'));

    $this->assertDatabaseMissing('prescriptions', ['id' => $prescription->id]);
});

it('hides another doctor prescription from the current doctor', function () {
    $doctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();
    $prescription = Prescription::factory()
        ->for(Appointment::factory()->for($otherDoctor, 'doctor'), 'appointment')
        ->create();

    $this->actingAs($doctor->user)
        ->get(route('prescriptions.show', $prescription))
        ->assertNotFound();

    $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id]);
});

it('renders the printable prescription from its appointment relationships', function () {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->for($doctor, 'doctor')->create();
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create([
        'diagnosis' => 'Annual checkup',
        'medicines' => 'No medication required.',
    ]);

    $this->actingAs($doctor->user)
        ->get(route('prescriptions.print', $prescription))
        ->assertOk()
        ->assertSee('Annual checkup')
        ->assertSee('No medication required.');
});

it('forbids patients from accessing prescription management', function () {
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)
        ->get(route('prescriptions.index'))
        ->assertForbidden();
});

it('preselects the accessible appointment when prescribing from a visit', function () {
    $appointment = Appointment::factory()->create(['status' => 'confirmed']);

    $this->actingAs($appointment->doctor->user)
        ->get(route('prescriptions.create', ['appointment_id' => $appointment->id]))
        ->assertViewHas('prescription', fn ($prescription) => $prescription->appointment_id === $appointment->id)
        ->assertSee('value="'.$appointment->id.'" selected', false);
});

it('does not preselect another doctors appointment', function () {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($doctor->user)->get(route('prescriptions.create', ['appointment_id' => $appointment->id]))
        ->assertNotFound();
});

it('validates appointment selection before rendering the prescription form', function (mixed $appointmentId) {
    $this->actingAs(User::factory()->doctor()->create())
        ->getJson(route('prescriptions.create', ['appointment_id' => $appointmentId]))
        ->assertUnprocessable()->assertJsonValidationErrors('appointment_id');
})->with(['not an id' => ['invalid'], 'negative id' => [-1]]);
