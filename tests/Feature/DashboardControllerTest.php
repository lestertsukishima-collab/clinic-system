<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;

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
