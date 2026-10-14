<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = app(ClinicSeeder::class)->demoPasswords()['doctor'];

        foreach ([
            ['name' => 'Dr. Ana Santos', 'email' => 'ana.santos@clinic.com', 'specialization' => 'Pediatrics', 'phone' => '09170000001'],
            ['name' => 'Dr. Miguel Reyes', 'email' => 'miguel.reyes@clinic.com', 'specialization' => 'Internal Medicine', 'phone' => '09170000002'],
        ] as $attributes) {
            $user = User::firstOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => Hash::make($password),
                    'role' => 'doctor',
                    'email_verified_at' => now(),
                ]
            );

            if (app()->isProduction() && Hash::check('password123', $user->password)) {
                $user->update(['password' => Hash::make($password)]);
            }

            Doctor::firstOrCreate(
                ['user_id' => $user->id],
                ['specialization' => $attributes['specialization'], 'phone' => $attributes['phone']]
            );
        }
    }
}
