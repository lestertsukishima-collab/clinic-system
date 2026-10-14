<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appointments = Appointment::where('status', 'completed')
            ->where('notes', 'like', 'Demo appointment: % / completed.')
            ->get();

        foreach ($appointments as $appointment) {
            Prescription::firstOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'diagnosis' => 'Sample consultation diagnosis (demonstration only).',
                    'medicines' => 'Sample medicine entry (demonstration only; not for clinical use).',
                    'instructions' => 'Sample follow-up instructions for testing the prescription workflow.',
                ]
            );
        }
    }
}
