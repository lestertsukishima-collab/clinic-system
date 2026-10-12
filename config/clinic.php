<?php

return [
    'timezone' => env('CLINIC_TIMEZONE', 'Asia/Manila'),
    'appointment_duration_minutes' => max(1, (int) env('CLINIC_APPOINTMENT_DURATION_MINUTES', 30)),
    'demo_passwords' => [
        'admin' => env('CLINIC_DEMO_ADMIN_PASSWORD'),
        'doctor' => env('CLINIC_DEMO_DOCTOR_PASSWORD'),
        'patient' => env('CLINIC_DEMO_PATIENT_PASSWORD'),
    ],
];
