<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => User::factory()->patient(),
            'doctor_id' => Doctor::factory(),
            'service_id' => Service::factory(),
            'appointment_date' => now()->addDays(3)->setTime(9, 0),
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
