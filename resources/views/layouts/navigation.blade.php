<aside id="clinic-sidebar" x-ref="sidebar" class="clinic-sidebar" :class="{ 'is-open': sidebarOpen }" @keydown.tab="trapSidebarFocus($event)">
    <div class="clinic-sidebar-header">
        <a href="{{ route('dashboard') }}" class="clinic-app-brand">
            <span class="clinic-app-brand-mark" aria-hidden="true"><i class="bi bi-hospital"></i></span>
            <span class="clinic-app-brand-copy"><strong>Happy Clinic</strong><small>Care management</small></span>
        </a>
        <button x-ref="sidebarClose" type="button" class="clinic-sidebar-close" @click="closeSidebar()" aria-label="Close navigation menu">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="clinic-sidebar-menu" aria-label="Main navigation">
        <p class="clinic-sidebar-label">{{ __('Workspace') }}</p>
        <x-responsive-nav-link class="clinic-sidebar-link" :href="route('dashboard')" :active="request()->routeIs('dashboard')" :aria-current="request()->routeIs('dashboard') ? 'page' : null">
            <i class="bi bi-grid-1x2" aria-hidden="true"></i>
            {{ __('Dashboard') }}
        </x-responsive-nav-link>
        <x-responsive-nav-link class="clinic-sidebar-link" :href="route('appointments.index')" :active="request()->routeIs('appointments.*')" :aria-current="request()->routeIs('appointments.*') ? 'page' : null">
            <i class="bi bi-calendar2-week" aria-hidden="true"></i>
            {{ __('Appointments') }}
        </x-responsive-nav-link>
        @if(in_array(Auth::user()->role, ['doctor', 'admin'], true))
            <x-responsive-nav-link class="clinic-sidebar-link" :href="route('prescriptions.index')" :active="request()->routeIs('prescriptions.*')" :aria-current="request()->routeIs('prescriptions.*') ? 'page' : null">
                <i class="bi bi-prescription2" aria-hidden="true"></i>
                {{ __('Prescriptions') }}
            </x-responsive-nav-link>
        @endif
        @if(Auth::user()->role === 'admin')
            <x-responsive-nav-link class="clinic-sidebar-link" :href="route('doctors.index')" :active="request()->routeIs('doctors.*')" :aria-current="request()->routeIs('doctors.*') ? 'page' : null">
                <i class="bi bi-person-badge" aria-hidden="true"></i>
                {{ __('Doctors') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link class="clinic-sidebar-link" :href="route('services.index')" :active="request()->routeIs('services.*')" :aria-current="request()->routeIs('services.*') ? 'page' : null">
                <i class="bi bi-heart-pulse" aria-hidden="true"></i>
                {{ __('Services') }}
            </x-responsive-nav-link>
        @endif
    </nav>

    <div class="clinic-sidebar-account">
        <div class="clinic-sidebar-user">
            @if (Auth::user()->profile_photo)
                <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="" class="clinic-nav-avatar">
            @else
                <span class="clinic-nav-avatar-placeholder" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
            @endif
            <div class="clinic-sidebar-user-copy">
                <strong>{{ Auth::user()->doctor?->display_name ?? Auth::user()->name }}</strong>
                <small>{{ ucfirst(Auth::user()->role) }}</small>
            </div>
        </div>
        <x-responsive-nav-link class="clinic-sidebar-link" :href="route('profile.edit')" :active="request()->routeIs('profile.*')" :aria-current="request()->routeIs('profile.*') ? 'page' : null">
            <i class="bi bi-person-circle" aria-hidden="true"></i>
            {{ __('Profile') }}
        </x-responsive-nav-link>
        <form method="POST" action="{{ route('logout') }}" onsubmit="return window.confirm('Are you sure you want to log out?');">
            @csrf
            <button type="submit" class="clinic-sidebar-link clinic-sidebar-logout">
                <i class="bi bi-box-arrow-left" aria-hidden="true"></i>
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</aside>
