<?php

return [
    'timezone' => env('CLINIC_TIMEZONE', 'Asia/Manila'),
    'appointment_duration_minutes' => max(1, (int) env('CLINIC_APPOINTMENT_DURATION_MINUTES', 30)),
    'admin' => [
        'name' => env('CLINIC_ADMIN_NAME'),
        'email' => env('CLINIC_ADMIN_EMAIL'),
        'password' => env('CLINIC_ADMIN_PASSWORD'),
    ],
    'demo_passwords' => [
        'admin' => env('CLINIC_DEMO_ADMIN_PASSWORD'),
        'doctor' => env('CLINIC_DEMO_DOCTOR_PASSWORD'),
        'patient' => env('CLINIC_DEMO_PATIENT_PASSWORD'),
    ],
];
