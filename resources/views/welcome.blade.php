<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('herd-favicon.png') }}?v=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ config('app.name', 'Happy Clinic') }}</title>
</head>
<body class="font-sans clinic-home-page" id="top">
    <header class="clinic-home-header">
        <div class="clinic-home-nav">
            <a class="clinic-home-brand" href="{{ url('/') }}" aria-label="Happy Clinic home">
                <span class="clinic-home-brand-icon" aria-hidden="true"><i class="bi bi-hospital"></i></span>
                <span>
                    <strong>Happy Clinic</strong>
                    <small>Care that puts you first</small>
                </span>
            </a>

            <nav class="clinic-home-nav-links" aria-label="Main navigation">
                <a href="#care">Our approach</a>
                <a href="#services">How it works</a>
            </nav>

            <div class="clinic-home-nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="clinic-home-login-link">Dashboard</a>
                    <a href="{{ route('dashboard') }}" class="clinic-home-button clinic-home-button-small">
                        Open dashboard <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                @else
                    @if(Route::has('login'))
                        <a href="{{ route('login') }}" class="clinic-home-login-link">Log in</a>
                    @endif
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="clinic-home-button clinic-home-button-small">Get started</a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="clinic-home-hero" id="care">
            <div class="clinic-home-hero-inner">
                <div class="clinic-home-hero-copy">
                    <span class="clinic-home-eyebrow"><span></span> THOUGHTFUL CARE, MADE SIMPLE</span>
                    <h1>Good health starts with feeling <em>well cared for.</em></h1>
                    <p class="clinic-home-intro">
                        Welcome to Happy Clinic. Book appointments with ease, connect with your care team, and keep your clinic visits organized in one place.
                    </p>

                    <div class="clinic-home-hero-actions">
                        @auth
                            @if(auth()->user()->role === 'doctor')
                                <a href="{{ route('dashboard') }}" id="bookAppointmentBtn" class="clinic-home-button">
                                    View appointments <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            @else
                                <a href="{{ route('appointments.create') }}" id="bookAppointmentBtn" class="clinic-home-button">
                                    Book an appointment <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            @endif
                        @else
                            @if(Route::has('register'))
                                <a href="{{ route('register') }}" id="getStartedBtn" class="clinic-home-button">
                                    Get started <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            @endif
                            @if(Route::has('login'))
                                <a href="{{ route('login') }}" class="clinic-home-secondary-button">Log in to your account</a>
                            @endif
                        @endauth
                    </div>

                    <div class="clinic-home-reassurance">
                        <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Simple appointment requests</span>
                        <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Care managed by your clinic team</span>
                    </div>
                </div>

                <div class="clinic-home-visual" aria-label="Happy Clinic care overview">
                    <div class="clinic-home-orbit clinic-home-orbit-one"></div>
                    <div class="clinic-home-orbit clinic-home-orbit-two"></div>
                    <div class="clinic-home-visual-card">
                        <div class="clinic-home-visual-topline">
                            <span class="clinic-home-visual-mark"><i class="bi bi-heart-pulse" aria-hidden="true"></i></span>
                            <span class="clinic-home-open"><span></span> HERE FOR YOUR HEALTH</span>
                        </div>
                        <div class="clinic-home-illustration" aria-hidden="true">
                            <div class="clinic-home-cross"><i class="bi bi-plus-lg"></i></div>
                            <i class="bi bi-heart-pulse clinic-home-heart"></i>
                            <div class="clinic-home-illustration-line clinic-home-line-one"></div>
                            <div class="clinic-home-illustration-line clinic-home-line-two"></div>
                        </div>
                        <div class="clinic-home-visual-caption">
                            <span class="clinic-home-caption-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
                            <div>
                                <strong>Your care, in one place</strong>
                                <p>Appointments and prescriptions, made easier to manage.</p>
                            </div>
                        </div>
                    </div>
                    <span class="clinic-home-spark clinic-home-spark-one" aria-hidden="true">✳</span>
                    <span class="clinic-home-spark clinic-home-spark-two" aria-hidden="true">✦</span>
                </div>
            </div>
        </section>

        <section class="clinic-home-features" id="services">
            <div class="clinic-home-section-heading">
                <span class="clinic-home-eyebrow"><span></span> A BETTER CLINIC EXPERIENCE</span>
                <h2>Care feels easier when everything is clear.</h2>
                <p>Helpful tools for patients and the people who care for them.</p>
            </div>

            <div class="clinic-home-feature-grid">
                <article class="clinic-home-feature-card">
                    <span class="clinic-home-feature-icon clinic-icon-mint" aria-hidden="true"><i class="bi bi-calendar2-plus"></i></span>
                    <h3>Book with confidence</h3>
                    <p>Request an appointment and see its status from your clinic account.</p>
                </article>
                <article class="clinic-home-feature-card">
                    <span class="clinic-home-feature-icon clinic-icon-blue" aria-hidden="true"><i class="bi bi-people"></i></span>
                    <h3>Stay connected to your care</h3>
                    <p>Your appointments are organized with the right doctor and clinic service.</p>
                </article>
                <article class="clinic-home-feature-card">
                    <span class="clinic-home-feature-icon clinic-icon-peach" aria-hidden="true"><i class="bi bi-prescription2"></i></span>
                    <h3>Keep care details together</h3>
                    <p>Doctors can prepare and manage prescriptions alongside appointment details.</p>
                </article>
            </div>
        </section>

        <section class="clinic-home-bottom-cta">
            <div>
                <span class="clinic-home-eyebrow clinic-home-eyebrow-light"><span></span> WE’RE HERE TO HELP</span>
                <h2>Take the next step in your care.</h2>
                <p>Sign in to manage your clinic visits or create an account to get started.</p>
            </div>
            <div class="clinic-home-bottom-actions">
                @auth
                    @if(auth()->user()->role === 'doctor')
                        <a href="{{ route('dashboard') }}" class="clinic-home-button clinic-home-button-white">Go to dashboard</a>
                    @else
                        <a href="{{ route('appointments.create') }}" class="clinic-home-button clinic-home-button-white">Book an appointment</a>
                    @endif
                @else
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="clinic-home-button clinic-home-button-white">Create an account</a>
                    @endif
                    @if(Route::has('login'))
                        <a href="{{ route('login') }}" class="clinic-home-bottom-login">Log in</a>
                    @endif
                @endauth
            </div>
        </section>
    </main>

    <footer class="clinic-home-footer">
        <a class="clinic-home-brand clinic-home-footer-brand" href="{{ url('/') }}">
            <span class="clinic-home-brand-icon" aria-hidden="true"><i class="bi bi-hospital"></i></span>
            <span><strong>Happy Clinic</strong><small>Care that puts you first</small></span>
        </a>
        <span>© {{ date('Y') }} Happy Clinic. Your health matters.</span>
    </footer>
</body>
</html>
