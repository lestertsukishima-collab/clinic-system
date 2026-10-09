<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;

it('shows clinic-wide monthly request and staffing metrics to administrators', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $firstPatient = User::factory()->patient()->create();
    $secondPatient = User::factory()->patient()->create();
    $service = Service::factory()->create();
    $monthStart = now()->startOfMonth();

    Appointment::factory()->for($firstPatient, 'patient')->for($doctor, 'doctor')->for($service, 'service')->create([
        'created_at' => $monthStart->copy()->addDay(),
        'updated_at' => $monthStart->copy()->addDay(),
        'status' => 'pending',
    ]);
    Appointment::factory()->for($firstPatient, 'patient')->for($doctor, 'doctor')->for($service, 'service')->create([
        'created_at' => $monthStart->copy()->addDays(2),
        'updated_at' => $monthStart->copy()->addDays(2),
        'status' => 'confirmed',
    ]);
    Appointment::factory()->for($secondPatient, 'patient')->for($doctor, 'doctor')->for($service, 'service')->create([
        'created_at' => $monthStart->copy()->addDays(3),
        'updated_at' => $monthStart->copy()->addDays(3),
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Clinic overview')
        ->assertSee('Appointment requests')
        ->assertSee('from 2 patients')
        ->assertSee('Pending requests')
        ->assertSee('patient accounts')
        ->assertSee('Recent appointment requests')
        ->assertSee($firstPatient->name)
        ->assertSee($secondPatient->name);
});

it('shows today in clinic time with accurate counts and an ordered active schedule', function () {
    config(['clinic.timezone' => 'Asia/Manila']);
    $this->travelTo(Carbon::parse('2026-10-08 18:00:00', 'UTC'));
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->patient()->create();
    $service = Service::factory()->create();
    $appointments = Appointment::factory()->recycle([$doctor, $patient, $service])->count(6)->sequence(
        ['appointment_date' => '2026-10-08 15:59:59', 'status' => 'confirmed'],
        ['appointment_date' => '2026-10-08 16:00:00', 'status' => 'pending'],
        ['appointment_date' => '2026-10-09 15:59:59', 'status' => 'confirmed'],
        ['appointment_date' => '2026-10-09 16:00:00', 'status' => 'confirmed'],
        ['appointment_date' => '2026-10-09 04:00:00', 'status' => 'completed'],
        ['appointment_date' => '2026-10-09 05:00:00', 'status' => 'cancelled'],
    )->create();

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertSee('Fri, Oct 9, 2026')
        ->assertSee(route('appointments.index', ['status' => 'pending']), false)
        ->assertSee(route('appointments.index', ['date' => '2026-10-09']), false)
        ->assertViewHas('todayAppointmentCount', 4)
        ->assertViewHas('todayConfirmedCount', 1)
        ->assertViewHas('todayCompletedCount', 1)
        ->assertViewHas('todayAppointments', fn ($today) => $today->modelKeys() === [$appointments[1]->id, $appointments[2]->id]);
});

it('shows a useful empty admin schedule', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
        ->assertSee('No pending or confirmed appointments today.')
        ->assertSee(route('appointments.create'), false)
        ->assertViewHas('todayAppointmentCount', 0);
});

it('does not show administration navigation or clinic metrics to other roles', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))->get(route('dashboard'))
        ->assertDontSee('ADMINISTRATOR WORKSPACE')
        ->assertDontSee(route('patients.index'), false)
        ->assertViewIs($role === 'doctor' ? 'doctor-dashboard' : 'dashboard');
})->with(['patient', 'doctor']);

it('shows the original submission timestamp separately from the scheduled visit across related pages', function (string $role) {
    config(['clinic.timezone' => 'Asia/Manila']);
    $this->travelTo(Carbon::parse('2026-10-09 00:00:00', 'UTC'));
    $appointment = Appointment::factory()->create([
        'created_at' => '2026-09-30 16:52:14',
        'updated_at' => '2026-10-04 06:00:00',
        'appointment_date' => '2026-10-05 02:00:00',
        'status' => 'completed',
    ]);
    $prescription = Prescription::factory()->for($appointment, 'appointment')->create();
    $user = match ($role) {
        'patient' => $appointment->patient,
        'doctor' => $appointment->doctor->user,
        'admin' => User::factory()->admin()->create(),
    };
    $this->actingAs($user);

    foreach ([route('dashboard'), route('appointments.index'), route('appointments.show', $appointment)] as $url) {
        $this->get($url)->assertOk()
            ->assertSee('Requested on')
            ->assertSee('Oct 01, 2026')
            ->assertSee('12:52:14 AM')
            ->assertSee('Scheduled visit')
            ->assertSee('Oct 05, 2026')
            ->assertSee('10:00 AM');
    }

    $this->get(route($role === 'patient' ? 'patient-prescriptions.show' : 'prescriptions.show', $prescription))
        ->assertOk()->assertSee('Appointment requested on')
        ->assertSee('Oct 01, 2026')->assertSee('12:52:14 AM');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'created_at' => '2026-09-30 16:52:14',
        'updated_at' => '2026-10-04 06:00:00',
        'appointment_date' => '2026-10-05 02:00:00',
    ]);
})->with(['patient', 'doctor', 'admin']);
