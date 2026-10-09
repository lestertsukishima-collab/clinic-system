<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;

it('shows only the doctors workload using clinic day boundaries and chronological order', function () {
    config(['clinic.timezone' => 'Asia/Manila']);
    $this->travelTo(Carbon::parse('2026-10-09 00:00:00', 'UTC'));
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->patient()->create();
    $service = Service::factory()->create();
    $appointments = Appointment::factory()->for($doctor, 'doctor')->for($patient, 'patient')->for($service, 'service')->count(6)->sequence(
        ['appointment_date' => '2026-10-08 15:59:59', 'status' => 'confirmed'],
        ['appointment_date' => '2026-10-08 16:00:00', 'status' => 'completed'],
        ['appointment_date' => '2026-10-08 23:00:00', 'status' => 'pending'],
        ['appointment_date' => '2026-10-09 01:00:00', 'status' => 'confirmed'],
        ['appointment_date' => '2026-10-09 15:59:59', 'status' => 'confirmed'],
        ['appointment_date' => '2026-10-09 16:00:00', 'status' => 'pending'],
    )->create();
    Prescription::factory()->for($appointments[1], 'appointment')->create();
    $otherAppointment = Appointment::factory()->create(['appointment_date' => '2026-10-09 00:15:00']);
    Prescription::factory()->for($otherAppointment, 'appointment')->create();

    $this->actingAs($doctor->user)->get(route('dashboard', ['schedule' => 'today']))
        ->assertViewIs('doctor-dashboard')
        ->assertSee('My clinical workspace')
        ->assertSee($doctor->display_name)
        ->assertSee('Fri, Oct 9, 2026')
        ->assertDontSee($otherAppointment->patient->name)
        ->assertViewHas('todayCount', 4)
        ->assertViewHas('pendingCount', 2)
        ->assertViewHas('completedTodayCount', 1)
        ->assertViewHas('prescriptionCount', 1)
        ->assertViewHas('scheduleAppointments', fn ($today) => $today->modelKeys() === [$appointments[1]->id, $appointments[2]->id, $appointments[3]->id, $appointments[4]->id])
        ->assertViewHas('nextAppointment', fn ($next) => $next->is($appointments[3]))
        ->assertSee(route('appointments.confirm', $appointments[5]), false)
        ->assertDontSee(route('appointments.confirm', $appointments[2]), false)
        ->assertSee('Reschedule and confirm')
        ->assertSee('choose a new visit time before confirming');
});

it('keeps future visits out of the next confirmed visit when they are cancelled or completed', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    Appointment::factory()->for($doctor, 'doctor')->count(2)->sequence(['status' => 'cancelled'], ['status' => 'completed'])->create();

    $this->actingAs($doctor->user)->get(route('dashboard'))
        ->assertViewHas('nextAppointment', null)
        ->assertSee('No upcoming confirmed visits.');
});

it('paginates todays appointments and bounds the pending preview', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->patient()->create();
    $service = Service::factory()->create();
    $appointments = Appointment::factory()->for($doctor, 'doctor')->for($patient, 'patient')->for($service, 'service')
        ->count(11)->create(['appointment_date' => now()]);

    $this->actingAs($doctor->user)->get(route('dashboard', ['schedule' => 'today', 'page' => 2]))
        ->assertViewHas('todayCount', 11)
        ->assertViewHas('scheduleAppointments', fn ($today) => $today->modelKeys() === [$appointments->last()->id] && str_contains($today->previousPageUrl(), 'schedule=today'))
        ->assertViewHas('pendingAppointments', fn ($pending) => $pending->modelKeys() === $appointments->take(5)->modelKeys());
});

it('shows useful empty states for a doctor without appointments', function () {
    $doctor = Doctor::factory()->create();

    $this->actingAs($doctor->user)->get(route('dashboard'))
        ->assertSee('No appointments assigned yet')
        ->assertSee("You're up to date.", false)
        ->assertViewHas('todayCount', 0)
        ->assertViewHas('pendingCount', 0)
        ->assertViewHas('prescriptionCount', 0);
});

it('explains a missing doctor profile without revealing clinic records', function () {
    $user = User::factory()->doctor()->create();
    $appointment = Appointment::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertSee('Your doctor profile is not linked yet.')
        ->assertDontSee($appointment->patient->name)
        ->assertViewHas('todayCount', 0);
});

it('escapes patient names and service text in the doctor workspace', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->patient()->create(['name' => '<script>patient()</script>']);
    $service = Service::factory()->create(['name' => '<script>service()</script>']);
    Appointment::factory()->for($doctor, 'doctor')->for($patient, 'patient')->for($service, 'service')->create(['appointment_date' => now()]);

    $this->actingAs($doctor->user)->get(route('dashboard'))
        ->assertSee($patient->name)->assertDontSee($patient->name, false)
        ->assertSee($service->name)->assertDontSee($service->name, false);
});

it('shows existing past and future appointments of every status by default without exposing another doctors records', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->patient()->create();
    $service = Service::factory()->create();
    $appointments = Appointment::factory()->for($doctor, 'doctor')->for($patient, 'patient')->for($service, 'service')->count(4)->sequence(
        ['appointment_date' => now()->subDays(3), 'status' => 'completed'],
        ['appointment_date' => now()->subDays(2), 'status' => 'cancelled'],
        ['appointment_date' => now()->addDay(), 'status' => 'confirmed'],
        ['appointment_date' => now()->addDays(2), 'status' => 'pending'],
    )->create();
    $other = Appointment::factory()->create();

    $this->actingAs($doctor->user)->get(route('dashboard'))
        ->assertSee('All appointments (4)')
        ->assertViewHas('todayCount', 0)
        ->assertViewHas('schedule', 'all')
        ->assertViewHas('scheduleAppointments', fn ($schedule) => $schedule->modelKeys() === [$appointments[3]->id, $appointments[2]->id, $appointments[1]->id, $appointments[0]->id])
        ->assertDontSee($other->patient->name);

    $this->assertDatabaseCount('appointments', 5);
});

it('preserves the full appointment list across dashboard pages', function () {
    $this->freezeTime();
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->patient()->create();
    $service = Service::factory()->create();
    $appointments = Appointment::factory()->for($doctor, 'doctor')->for($patient, 'patient')->for($service, 'service')
        ->count(11)->create(['appointment_date' => now()->subMonth(), 'status' => 'completed']);

    $this->actingAs($doctor->user)->get(route('dashboard', ['page' => 2]))
        ->assertViewHas('scheduleAppointments', fn ($schedule) => $schedule->modelKeys() === [$appointments->last()->id] && str_contains($schedule->previousPageUrl(), 'schedule=all'));
});

it('shows how to return to existing appointments when the today view is empty', function () {
    $this->freezeTime();
    $appointment = Appointment::factory()->create(['appointment_date' => now()->subDay(), 'status' => 'completed']);

    $this->actingAs($appointment->doctor->user)->get(route('dashboard', ['schedule' => 'today']))
        ->assertSee('No appointments today')
        ->assertSee('All appointments (1)')
        ->assertSee('Choose All appointments to see past and upcoming visits.');
});

it('rejects unknown dashboard schedule filters', function () {
    $this->actingAs(User::factory()->doctor()->create())
        ->getJson(route('dashboard', ['schedule' => 'invalid']))
        ->assertUnprocessable()->assertJsonValidationErrors('schedule');
});
