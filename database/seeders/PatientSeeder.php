<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = app(ClinicSeeder::class)->demoPasswords()['patient'];

        foreach ([
            ['name' => 'Jose Garcia', 'email' => 'jose.garcia@example.com'],
            ['name' => 'Sofia Mendoza', 'email' => 'sofia.mendoza@example.com'],
            ['name' => 'Carlo Ramos', 'email' => 'carlo.ramos@example.com'],
            ['name' => 'Isabel Torres', 'email' => 'isabel.torres@example.com'],
        ] as $attributes) {
            $user = User::firstOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => Hash::make($password),
                    'role' => 'patient',
                    'email_verified_at' => now(),
                ]
            );

            if (app()->isProduction() && Hash::check('password123', $user->password)) {
                $user->update(['password' => Hash::make($password)]);
            }
        }
    }
}
