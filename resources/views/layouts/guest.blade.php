<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('herd-favicon.png') }}?v=1">

        <title>Happy Clinic | Secure access</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased clinic-auth-page">
        <main class="clinic-auth-main">
            <div class="clinic-auth-decoration clinic-auth-decoration-left" aria-hidden="true"></div>
            <div class="clinic-auth-decoration clinic-auth-decoration-right" aria-hidden="true"></div>

            <div class="clinic-auth-shell">
                <header class="clinic-auth-header">
                    <a class="clinic-auth-brand" href="{{ url('/') }}">
                        <span class="clinic-auth-brand-icon" aria-hidden="true"><i class="bi bi-hospital"></i></span>
                        <span>
                            <strong>Happy Clinic</strong>
                            <small>Care that puts you first</small>
                        </span>
                    </a>
                    <a class="clinic-auth-home-link" href="{{ url('/') }}">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Clinic home
                    </a>
                </header>

                <section class="clinic-auth-card">
                {{ $slot }}
                </section>

                <p class="clinic-auth-footer"><i class="bi bi-shield-check" aria-hidden="true"></i> Your clinic account is protected and private.</p>
            </div>
        </main>
        <x-flash-toast />
    </body>
</html>
