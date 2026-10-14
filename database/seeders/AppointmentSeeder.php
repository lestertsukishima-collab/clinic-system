<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = User::where('role', 'patient')->whereIn('email', [
            'patient@gmail.com',
            'jose.garcia@example.com',
            'sofia.mendoza@example.com',
            'carlo.ramos@example.com',
            'isabel.torres@example.com',
        ])->orderBy('email')->get();
        $doctor = Doctor::whereHas('user', fn (Builder $query): Builder => $query->where('email', 'doctor@clinic.com'))->firstOrFail();
        $service = Service::where('name', 'General Consultation')->firstOrFail();

        foreach ($patients as $patientIndex => $patient) {
            foreach (['pending' => 3, 'confirmed' => 4, 'completed' => -3, 'cancelled' => -2] as $status => $days) {
                Appointment::firstOrCreate(
                    [
                        'patient_id' => $patient->id,
                        'notes' => "Demo appointment: {$patient->email} / {$status}.",
                    ],
                    [
                        'doctor_id' => $doctor->id,
                        'service_id' => $service->id,
                        'appointment_date' => now(config('clinic.timezone'))->addDays($days)->setTime(9, 0)
                            ->addMinutes($patientIndex * config('clinic.appointment_duration_minutes'))
                            ->setTimezone(config('app.timezone')),
                        'status' => $status,
                    ]
                );
            }
        }
    }
}
