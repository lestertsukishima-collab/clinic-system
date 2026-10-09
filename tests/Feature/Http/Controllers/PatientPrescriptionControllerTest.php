<?php

use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\User;

it('lists only the patients prescriptions from completed visits', function () {
    $patient = User::factory()->patient()->create();
    $completed = Appointment::factory()->for($patient, 'patient')->create(['status' => 'completed']);
    $prescription = Prescription::factory()->for($completed, 'appointment')->create();
    $pending = Appointment::factory()->for($patient, 'patient')->create();
    Prescription::factory()->for($pending, 'appointment')->create();
    Prescription::factory()->for(Appointment::factory()->state(['status' => 'completed']), 'appointment')->create();

    $this->actingAs($patient)->get(route('patient-prescriptions.index'))
        ->assertSee('My prescriptions')
        ->assertViewHas('prescriptions', fn ($records) => $records->modelKeys() === [$prescription->id])
        ->assertSee(route('patient-prescriptions.show', $prescription), false)
        ->assertDontSee(route('prescriptions.create'), false);
});

it('lets patients read and print their own completed visit prescription', function () {
    $appointment = Appointment::factory()->create(['status' => 'completed']);
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create();

    $this->actingAs($appointment->patient)->get(route('patient-prescriptions.show', $prescription))
        ->assertSee($prescription->diagnosis)
        ->assertSee($prescription->medicines)
        ->assertSee(route('patient-prescriptions.print', $prescription), false)
        ->assertDontSee(route('prescriptions.edit', $prescription), false)
        ->assertDontSee('Delete');
    $this->get(route('patient-prescriptions.print', $prescription))
        ->assertSee($prescription->medicines)
        ->assertSee($appointment->doctor->display_name)
        ->assertDontSee('123 Medical Center Way')
        ->assertDontSee('XXXXX');
});

it('hides another patients prescription when accessed directly', function (string $action) {
    $patient = User::factory()->patient()->create();
    $prescription = Prescription::factory()->for(Appointment::factory()->state(['status' => 'completed']), 'appointment')->create();

    $this->actingAs($patient)->get(route('patient-prescriptions.'.$action, $prescription))->assertNotFound();
})->with(['show', 'print']);

it('hides prescriptions until the patients visit is completed', function (string $status, string $action) {
    $appointment = Appointment::factory()->create(['status' => $status]);
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create();

    $this->actingAs($appointment->patient)->get(route('patient-prescriptions.'.$action, $prescription))->assertNotFound();
})->with(['pending', 'confirmed', 'cancelled'])->with(['show', 'print']);

it('requires authentication for patient prescription pages', function (string $action) {
    $prescription = Prescription::factory()->create();

    $this->get(route('patient-prescriptions.'.$action, $action === 'index' ? [] : $prescription))->assertRedirect(route('login'));
})->with(['index', 'show', 'print']);

it('keeps staff on the existing prescription management pages', function (string $role, string $action) {
    $prescription = Prescription::factory()->create();

    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('patient-prescriptions.'.$action, $action === 'index' ? [] : $prescription))->assertForbidden();
})->with(['admin', 'doctor'])->with(['index', 'show', 'print']);

it('does not let patients create update or delete prescriptions through management endpoints', function (string $method, string $action) {
    $appointment = Appointment::factory()->create(['status' => 'completed']);
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create(['diagnosis' => 'Original diagnosis']);

    $this->actingAs($appointment->patient)->{$method}(route('prescriptions.'.$action, $action === 'store' ? [] : $prescription), [
        'appointment_id' => $appointment->id,
        'diagnosis' => 'Changed diagnosis',
        'medicines' => 'Changed medicine',
    ])->assertForbidden();

    $this->assertDatabaseCount('prescriptions', 1);
    $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id, 'diagnosis' => 'Original diagnosis']);
})->with([['post', 'store'], ['put', 'update'], ['delete', 'destroy']]);

it('paginates prescription history', function () {
    $appointment = Appointment::factory()->create(['status' => 'completed']);
    $prescriptions = Prescription::factory()->for($appointment, 'appointment')->count(11)->create();

    $this->actingAs($appointment->patient)->get(route('patient-prescriptions.index', ['page' => 2]))
        ->assertViewHas('prescriptions', fn ($records) => $records->total() === 11 && $records->modelKeys() === [$prescriptions->first()->id]);
});

it('shows a useful empty prescription list', function () {
    $this->actingAs(User::factory()->patient()->create())->get(route('patient-prescriptions.index'))
        ->assertSee('No prescriptions recorded')
        ->assertSee('Prescriptions will appear here after your doctor completes the visit')
        ->assertDontSee('Create a prescription');
});

it('escapes diagnosis medicines and instructions on patient prescription pages', function (string $action) {
    $appointment = Appointment::factory()->create(['status' => 'completed']);
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create([
        'diagnosis' => '<script>diagnosis()</script>',
        'medicines' => '<script>medicines()</script>',
        'instructions' => '<script>instructions()</script>',
    ]);

    $this->actingAs($appointment->patient)->get(route('patient-prescriptions.'.$action, $prescription))
        ->assertSee($prescription->diagnosis)->assertDontSee($prescription->diagnosis, false)
        ->assertSee($prescription->medicines)->assertDontSee($prescription->medicines, false)
        ->assertSee($prescription->instructions)->assertDontSee($prescription->instructions, false);
})->with(['show', 'print']);
