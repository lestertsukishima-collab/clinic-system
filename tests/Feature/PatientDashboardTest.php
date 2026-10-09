<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;

it('shows the patients own history and metrics with the nearest confirmed visit in clinic time', function () {
    config(['clinic.timezone' => 'Asia/Manila']);
    $this->travelTo(Carbon::parse('2026-10-08 17:00:00', 'UTC'));
    $patient = User::factory()->patient()->create();
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $appointments = Appointment::factory()->for($patient, 'patient')->for($doctor, 'doctor')->for($service, 'service')->count(6)->sequence(
        ['status' => 'completed', 'appointment_date' => now()->subDays(3)],
        ['status' => 'cancelled', 'appointment_date' => now()->subDay()],
        ['status' => 'confirmed', 'appointment_date' => now()->subHour()],
        ['status' => 'pending', 'appointment_date' => now()->addMinutes(30)],
        ['status' => 'confirmed', 'appointment_date' => now()->addHour()],
        ['status' => 'confirmed', 'appointment_date' => now()->addDays(2)],
    )->create();
    Prescription::factory()->for($appointments[0], 'appointment')->create();
    Prescription::factory()->for($appointments[3], 'appointment')->create();
    $other = Appointment::factory()->create(['status' => 'completed']);
    Prescription::factory()->for($other, 'appointment')->create();

    $this->actingAs($patient)->get(route('dashboard'))
        ->assertSee('PATIENT WORKSPACE')
        ->assertSee('All my appointments')
        ->assertSee('Fri, Oct 9, 2026')
        ->assertSee('2:00 AM')
        ->assertViewHas('appointments', fn ($history) => $history->modelKeys() === [$appointments[5]->id, $appointments[4]->id, $appointments[3]->id, $appointments[2]->id, $appointments[1]->id, $appointments[0]->id])
        ->assertViewHas('pendingCount', 1)
        ->assertViewHas('completedCount', 1)
        ->assertViewHas('prescriptionCount', 1)
        ->assertViewHas('nextAppointment', fn ($next) => $next->is($appointments[4]))
        ->assertSee(route('appointments.edit', $appointments[3]), false)
        ->assertDontSee(route('appointments.edit', $appointments[4]), false)
        ->assertDontSee(route('appointments.confirm', $appointments[3]), false)
        ->assertDontSee($other->doctor->display_name)
        ->assertDontSee(route('patients.index'), false);
});

it('shows a booking path and zero counts for a new patient', function () {
    $patient = User::factory()->patient()->create();
    Appointment::factory()->create();

    $this->actingAs($patient)->get(route('dashboard'))
        ->assertSee('No appointments yet')
        ->assertSee('Request your first appointment')
        ->assertViewHas('appointments', fn ($history) => $history->total() === 0)
        ->assertViewHas('pendingCount', 0)
        ->assertViewHas('completedCount', 0)
        ->assertViewHas('prescriptionCount', 0);
});

it('explains that pending requests are not confirmed appointments', function () {
    $this->freezeTime();
    $appointment = Appointment::factory()->create();

    $this->actingAs($appointment->patient)->get(route('dashboard'))
        ->assertViewHas('nextAppointment', null)
        ->assertSee('Your pending requests are still waiting for confirmation.')
        ->assertSee(route('appointments.index', ['status' => 'pending']), false);
});

it('keeps completed appointments accessible across history pages', function () {
    $this->freezeTime();
    $patient = User::factory()->patient()->create();
    $doctor = Doctor::factory()->create();
    $service = Service::factory()->create();
    $appointments = Appointment::factory()->for($patient, 'patient')->for($doctor, 'doctor')->for($service, 'service')
        ->count(11)->create(['status' => 'completed', 'appointment_date' => now()->subMonth()]);

    $this->actingAs($patient)->get(route('dashboard', ['page' => 2]))
        ->assertViewHas('appointments', fn ($history) => $history->total() === 11 && $history->modelKeys() === [$appointments->first()->id]);
});

it('escapes personal and service information on the patient dashboard', function () {
    $patient = User::factory()->patient()->create(['name' => '<script>patient()</script>']);
    $doctor = Doctor::factory()->for(User::factory()->doctor()->state(['name' => '<script>doctor()</script>']), 'user')->create();
    $service = Service::factory()->create(['name' => '<script>service()</script>']);
    Appointment::factory()->for($patient, 'patient')->for($doctor, 'doctor')->for($service, 'service')->create();

    $this->actingAs($patient)->get(route('dashboard'))
        ->assertSee($patient->name)->assertDontSee($patient->name, false)
        ->assertSee($doctor->display_name)->assertDontSee($doctor->display_name, false)
        ->assertSee($service->name)->assertDontSee($service->name, false);
});
