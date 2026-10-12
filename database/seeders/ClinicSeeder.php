<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $passwords = $this->demoPasswords();

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@clinic.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make($passwords['admin']),
                'role' => 'admin',
            ]
        );

        $doctorUser = User::firstOrCreate(
            ['email' => 'doctor@clinic.com'],
            [
                'name' => 'Dr. Juan Dela Cruz',
                'password' => Hash::make($passwords['doctor']),
                'role' => 'doctor',
            ]
        );

        $patientUser = User::firstOrCreate(
            ['email' => 'patient@gmail.com'],
            [
                'name' => 'Maria Clara',
                'password' => Hash::make($passwords['patient']),
                'role' => 'patient',
            ]
        );

        if (app()->isProduction()) {
            foreach (['admin' => $adminUser, 'doctor' => $doctorUser, 'patient' => $patientUser] as $role => $user) {
                if (Hash::check('password123', $user->password)) {
                    $user->update(['password' => Hash::make($passwords[$role])]);
                }
            }
        }

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

    /**
     * @return array{admin: string, doctor: string, patient: string}
     */
    private function demoPasswords(): array
    {
        $passwords = config('clinic.demo_passwords');

        if (app()->isProduction()) {
            $passwordRules = [
                'bail', 'required', 'string', 'min:12',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (strlen($value) > 72) {
                        $fail('Production demo passwords must not exceed 72 bytes.');
                    }
                },
            ];

            return Validator::make($passwords, [
                'admin' => $passwordRules,
                'doctor' => $passwordRules,
                'patient' => $passwordRules,
            ], [
                '*.required' => 'Set all three CLINIC_DEMO_*_PASSWORD environment variables before seeding production.',
                '*.min' => 'Production demo passwords must contain at least 12 characters.',
            ])->validate();
        }

        return [
            'admin' => $passwords['admin'] ?: 'password123',
            'doctor' => $passwords['doctor'] ?: 'password123',
            'patient' => $passwords['patient'] ?: 'password123',
        ];
    }
}
