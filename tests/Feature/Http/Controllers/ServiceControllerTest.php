<?php

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;

it('renders every service management page for administrators', function () {
    $admin = User::factory()->admin()->create();
    $service = Service::factory()->create();

    $this->actingAs($admin)->get(route('services.index'))->assertOk();
    $this->actingAs($admin)->get(route('services.create'))->assertOk();
    $this->actingAs($admin)->get(route('services.show', $service))->assertOk();
    $this->actingAs($admin)->get(route('services.edit', $service))->assertOk();
});

it('creates and updates a clinic service', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('services.store'), [
            'name' => 'Dental Consultation',
            'description' => 'Routine dental assessment.',
            'price' => '750.00',
        ])
        ->assertRedirect();

    $service = Service::query()->where('name', 'Dental Consultation')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('services.update', $service), [
            'name' => 'Dental Checkup',
            'description' => 'Updated description.',
            'price' => '800.00',
        ])
        ->assertRedirect(route('services.show', $service));

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => 'Dental Checkup',
        'description' => 'Updated description.',
        'price' => '800.00',
    ]);
});

it('deletes a service with no appointment history', function () {
    $admin = User::factory()->admin()->create();
    $service = Service::factory()->create();

    $this->actingAs($admin)
        ->delete(route('services.destroy', $service))
        ->assertRedirect(route('services.index'));

    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});

it('preserves services referenced by appointments', function () {
    $admin = User::factory()->admin()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($admin)
        ->from(route('services.show', $appointment->service))
        ->delete(route('services.destroy', $appointment->service))
        ->assertRedirect(route('services.show', $appointment->service))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('services', ['id' => $appointment->service_id]);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
});

it('rejects invalid service pricing without saving a service', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('services.create'))
        ->post(route('services.store'), [
            'name' => 'Invalid Service',
            'price' => '-1',
        ])
        ->assertSessionHasErrors(['price']);

    $this->assertDatabaseMissing('services', ['name' => 'Invalid Service']);
});
