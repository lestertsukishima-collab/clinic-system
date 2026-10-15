<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorUnavailability;
use Illuminate\Database\Seeder;

class DoctorUnavailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctor = Doctor::query()->first();

        if ($doctor !== null) {
            DoctorUnavailability::factory()->for($doctor)->create();
        }
    }
}
