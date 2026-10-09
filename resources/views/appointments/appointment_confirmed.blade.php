<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" href="{{ asset('herd-favicon.png') }}?v=1">
    <title>Appointment Confirmed</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2>Maayong Adlaw!</h2>
    <p>Ang imong appointment gi-confirm na sa clinic.</p>
    <p>{{ $appointment->local_appointment_date->format('M d, Y \a\t g:i A') }} ({{ config('clinic.timezone') }})</p>
    <p>Salamat!</p>
</body>
</html>
