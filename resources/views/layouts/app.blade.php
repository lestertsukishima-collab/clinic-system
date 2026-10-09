<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('herd-favicon.png') }}?v=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/bootstrap.min.css', 'resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div
            class="clinic-app-shell min-h-screen bg-gray-100"
            x-data="{
                sidebarOpen: false,
                closeSidebar() {
                    this.sidebarOpen = false;
                    this.$nextTick(() => this.$refs.sidebarToggle.focus());
                },
                trapSidebarFocus(event) {
                    if (!this.sidebarOpen) {
                        return;
                    }
                    const elements = [...this.$refs.sidebar.querySelectorAll('a[href], button')].filter(element => element.offsetParent !== null);
                    const first = elements[0];
                    const last = elements[elements.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            }"
            x-effect="document.body.style.overflow = sidebarOpen ? 'hidden' : ''"
            @keydown.escape.window="if (sidebarOpen) closeSidebar()"
            @resize.window.debounce.150ms="if (window.innerWidth >= 1024) sidebarOpen = false"
        >
            <div x-cloak x-show="sidebarOpen" class="clinic-sidebar-backdrop" @click="closeSidebar()" aria-hidden="true"></div>

            @include('layouts.navigation')

            <div class="clinic-app-content" :inert="sidebarOpen">
                <div class="clinic-app-header">
                    <div class="clinic-mobile-bar">
                        <button
                            x-ref="sidebarToggle"
                            type="button"
                            class="clinic-sidebar-toggle"
                            @click="sidebarOpen = true; $nextTick(() => $refs.sidebarClose.focus())"
                            :aria-expanded="sidebarOpen"
                            aria-controls="clinic-sidebar"
                            aria-label="Open navigation menu"
                        >
                            <i class="bi bi-list" aria-hidden="true"></i>
                        </button>
                        <span class="clinic-app-brand-copy"><strong>Happy Clinic</strong><small>{{ ucfirst(Auth::user()->role) }} workspace</small></span>
                    </div>

                    @isset($header)
                        <header class="bg-white shadow">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset
                </div>

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
        <x-flash-toast />
        <!-- Bootstrap 5 JS Bundle -->
    @vite('resources/js/bootstrap.bundle.min.js')
    </body>
</html>
