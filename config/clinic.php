<?php

return [
    'timezone' => env('CLINIC_TIMEZONE', 'Asia/Manila'),
    'appointment_duration_minutes' => max(1, (int) env('CLINIC_APPOINTMENT_DURATION_MINUTES', 30)),
];
