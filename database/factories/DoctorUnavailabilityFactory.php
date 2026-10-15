<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\DoctorUnavailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorUnavailability>
 */
class DoctorUnavailabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'starts_at' => now()->addDay()->startOfHour(),
            'ends_at' => now()->addDay()->startOfHour()->addHours(2),
        ];
    }
}
