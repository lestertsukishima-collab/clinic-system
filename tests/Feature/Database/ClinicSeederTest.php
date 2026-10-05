<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ClinicSeeder;

it('seeds one reusable clinic demo dataset without duplicating records', function () {
    $this->seed(ClinicSeeder::class);
    $this->seed(ClinicSeeder::class);

    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('doctors', 1);
    $this->assertDatabaseCount('services', 1);
    $this->assertDatabaseCount('appointments', 1);

    expect(User::where('email', 'doctor@clinic.com')->value('role'))->toBe('doctor')
        ->and(Doctor::with('user')->first()->user->role)->toBe('doctor')
        ->and(Service::first()->appointments)->toHaveCount(1)
        ->and(Appointment::with(['patient', 'doctor.user', 'service'])->first()->patient->role)->toBe('patient');
});
