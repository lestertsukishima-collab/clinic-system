<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'diagnosis' => fake()->sentence(),
            'medicines' => fake()->paragraph(),
            'instructions' => fake()->optional()->sentence(),
        ];
    }
}
