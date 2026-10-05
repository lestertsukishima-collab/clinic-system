<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@clinic.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $doctorUser = User::firstOrCreate(
            ['email' => 'doctor@clinic.com'],
            [
                'name' => 'Dr. Juan Dela Cruz',
                'password' => Hash::make('password123'),
                'role' => 'doctor',
            ]
        );

        $patientUser = User::firstOrCreate(
            ['email' => 'patient@gmail.com'],
            [
                'name' => 'Maria Clara',
                'password' => Hash::make('password123'),
                'role' => 'patient',
            ]
        );

        $doctor = Doctor::firstOrCreate(
            ['user_id' => $doctorUser->id],
            [
                'specialization' => 'General Medicine',
            ]
        );

        $service = Service::firstOrCreate(
            ['name' => 'General Consultation'],
            [
                'description' => 'Standard health checkup and medical advice.',
                'price' => 500.00,
            ]
        );

        Appointment::firstOrCreate(
            [
                'patient_id' => $patientUser->id,
                'doctor_id' => $doctor->id,
                'service_id' => $service->id,
            ],
            [
                'appointment_date' => now()->addDays(2),
                'status' => 'pending',
                'notes' => 'Sample appointment for local development.',
            ]
        );
    }
}
