<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\User;

it('searches records beyond the first page and returns filtered totals', function () {
    $admin = User::factory()->admin()->create();
    Service::factory()->count(12)->create(['name' => 'Care consultation']);
    Service::factory()->count(4)->create(['name' => 'Unrelated treatment']);

    $this->actingAs($admin)->getJson(route('services.index', [
        'draw' => 2, 'start' => 10, 'length' => 10, 'table_search' => 'Care',
    ]))->assertJsonPath('draw', 2)->assertJsonPath('recordsTotal', 16)
        ->assertJsonPath('recordsFiltered', 12)->assertJsonCount(2, 'data')
        ->assertSee('Care consultation')->assertDontSee('Unrelated treatment');
});

it('sorts prices numerically and returns the requested page', function () {
    $admin = User::factory()->admin()->create();
    Service::factory()->create(['name' => 'Ten peso service', 'price' => 10]);
    Service::factory()->create(['name' => 'Two peso service', 'price' => 2]);

    $response = $this->actingAs($admin)->getJson(route('services.index', [
        'draw' => 1, 'start' => 0, 'length' => 10,
        'order' => [['column' => 1, 'dir' => 'asc']],
    ]));

    expect($response->json('data.0'))->toContain('Two peso service');
    expect($response->json('data.1'))->toContain('Ten peso service');
});

it('sorts doctors by related account name', function () {
    $admin = User::factory()->admin()->create();
    Doctor::factory()->for(User::factory()->doctor()->create(['name' => 'Zulu Doctor']))->create();
    Doctor::factory()->for(User::factory()->doctor()->create(['name' => 'Alpha Doctor']))->create();

    $response = $this->actingAs($admin)->getJson(route('doctors.index', [
        'draw' => 1, 'start' => 0, 'length' => 15,
        'order' => [['column' => 0, 'dir' => 'asc']],
    ]));

    $response->assertJsonPath('recordsTotal', 2);
    expect($response->json('data.0'))->toContain('Alpha Doctor');
});

it('searches only patient accounts in the directory', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->patient()->create(['name' => 'Directory Match']);
    User::factory()->doctor()->create(['name' => 'Directory Match']);
    User::factory()->patient()->create(['name' => 'Other Patient']);

    $this->actingAs($admin)->getJson(route('patients.index', [
        'draw' => 1, 'start' => 0, 'length' => 15, 'table_search' => 'Directory Match',
    ]))->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 1)
        ->assertJsonCount(1, 'data')->assertSee('Directory Match');
});

it('keeps patient appointment searches and totals scoped to their account', function () {
    $patient = User::factory()->patient()->create();
    $service = Service::factory()->create(['name' => 'Private consultation']);
    $appointment = Appointment::factory()->for($patient, 'patient')->for($service)->create();
    $otherAppointment = Appointment::factory()->for($service)->create();

    $response = $this->actingAs($patient)->getJson(route('appointments.index', [
        'draw' => 1, 'start' => 0, 'length' => 10, 'table_search' => 'Private consultation',
        'patient_id' => $otherAppointment->patient_id,
    ]));

    $response->assertJsonPath('recordsTotal', 1)->assertJsonPath('recordsFiltered', 1)->assertJsonCount(1, 'data');
    expect($response->json('data.0'))->toContain(route('appointments.show', $appointment));
});

it('keeps doctor appointment searches scoped to assigned visits and preserves filters', function () {
    $doctor = Doctor::factory()->create();
    Appointment::factory()->for($doctor)->create(['status' => 'pending']);
    Appointment::factory()->for($doctor)->create(['status' => 'confirmed']);
    Appointment::factory()->create(['status' => 'pending']);

    $this->actingAs($doctor->user)->getJson(route('appointments.index', [
        'draw' => 1, 'start' => 0, 'length' => 10, 'status' => 'pending',
    ]))->assertJsonPath('recordsTotal', 1)->assertJsonCount(1, 'data')->assertSee('Pending')->assertDontSee('Confirmed');
});

it('sorts appointments by related patient names', function () {
    $admin = User::factory()->admin()->create();
    Appointment::factory()->for(User::factory()->patient()->create(['name' => 'Zulu Patient']), 'patient')->create();
    Appointment::factory()->for(User::factory()->patient()->create(['name' => 'Alpha Patient']), 'patient')->create();

    $response = $this->actingAs($admin)->getJson(route('appointments.index', [
        'draw' => 1, 'start' => 0, 'length' => 10, 'order' => [['column' => 0, 'dir' => 'asc']],
    ]));

    expect($response->json('data.0'))->toContain('Alpha Patient');
});

it('shows patients only their prescriptions from completed visits', function () {
    $patient = User::factory()->patient()->create();
    $completed = Appointment::factory()->for($patient, 'patient')->create(['status' => 'completed']);
    $pending = Appointment::factory()->for($patient, 'patient')->create(['status' => 'pending']);
    Prescription::factory()->for($completed)->create(['diagnosis' => 'Visible treatment']);
    Prescription::factory()->for($pending)->create(['diagnosis' => 'Unfinished treatment']);
    Prescription::factory()->create(['diagnosis' => 'Another patient treatment']);

    $this->actingAs($patient)->getJson(route('patient-prescriptions.index', [
        'draw' => 1, 'start' => 0, 'length' => 10, 'table_search' => 'treatment',
        'order' => [['column' => 2, 'dir' => 'desc']],
    ]))->assertJsonPath('recordsTotal', 1)->assertJsonCount(1, 'data')
        ->assertSee('Visible treatment')->assertDontSee('Unfinished treatment')->assertDontSee('Another patient treatment');
});

it('limits doctor prescription results to their assigned appointments', function () {
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->for($doctor)->create();
    Prescription::factory()->for($appointment)->create(['diagnosis' => 'Assigned diagnosis']);
    Prescription::factory()->create(['diagnosis' => 'Other diagnosis']);

    $this->actingAs($doctor->user)->getJson(route('prescriptions.index', [
        'draw' => 1, 'start' => 0, 'length' => 10, 'table_search' => 'diagnosis',
        'order' => [['column' => 1, 'dir' => 'asc']],
    ]))->assertJsonPath('recordsTotal', 1)->assertJsonCount(1, 'data')
        ->assertSee('Assigned diagnosis')->assertDontSee('Other diagnosis');
});

it('escapes user content in table rows and prevents response caching', function () {
    $admin = User::factory()->admin()->create();
    Service::factory()->create(['name' => '<script>alert(1)</script>']);

    $response = $this->actingAs($admin)->getJson(route('services.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    expect($response->json('data.0'))->toContain('&lt;script&gt;')->not->toContain('<script>');
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');
});

it('returns an empty result without a colspan placeholder row', function () {
    $this->actingAs(User::factory()->admin()->create())->getJson(route('services.index', [
        'draw' => 1, 'start' => 0, 'length' => 10,
    ]))->assertExactJson(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
});

it('rejects unsafe table parameters', function (array $parameters, string $field) {
    $this->actingAs(User::factory()->admin()->create())->getJson(route('services.index', array_replace([
        'draw' => 1, 'start' => 0, 'length' => 10,
    ], $parameters)))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'unbounded length' => [['length' => -1], 'length'],
    'oversized length' => [['length' => 101], 'length'],
    'negative offset' => [['start' => -1], 'start'],
    'invalid draw' => [['draw' => '<script>'], 'draw'],
    'array search' => [['table_search' => ['invalid']], 'table_search'],
    'oversized search' => [['table_search' => str_repeat('a', 101)], 'table_search'],
    'actions column' => [['order' => [['column' => 3, 'dir' => 'asc']]], 'order.0.column'],
    'column injection' => [['order' => [['column' => 'name desc', 'dir' => 'asc']]], 'order.0.column'],
    'direction injection' => [['order' => [['column' => 0, 'dir' => 'asc; DROP TABLE services']]], 'order.0.dir'],
]);

it('requires authentication for table data', function () {
    $this->getJson(route('services.index', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertUnauthorized();
});

it('forbids non administrators from requesting management table data', function (string $role, string $route) {
    $this->actingAs(User::factory()->create(['role' => $role]))->getJson(route($route, [
        'draw' => 1, 'start' => 0, 'length' => 10,
    ]))->assertForbidden();
})->with(['patient', 'doctor'])->with(['services.index', 'doctors.index', 'patients.index']);
