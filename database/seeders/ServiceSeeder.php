<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'General Consultation', 'description' => 'Standard health checkup and medical advice.', 'price' => 500.00],
            ['name' => 'Pediatric Consultation', 'description' => 'Health assessment for children and adolescents.', 'price' => 600.00],
            ['name' => 'Internal Medicine Consultation', 'description' => 'Adult health assessment and follow-up care.', 'price' => 700.00],
            ['name' => 'Annual Physical Examination', 'description' => 'Routine physical examination and health screening.', 'price' => 1000.00],
            ['name' => 'Follow-up Consultation', 'description' => 'Review of a previous consultation and care plan.', 'price' => 350.00],
        ] as $attributes) {
            Service::firstOrCreate(['name' => $attributes['name']], $attributes);
        }
    }
}
