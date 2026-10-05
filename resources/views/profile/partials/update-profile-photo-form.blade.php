<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Photo') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Choose a square JPG, PNG, or WebP image up to 2 MB. It will display as a circular profile photo.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf

        @if ($user->profile_photo)
            <img
                src="{{ asset('storage/' . $user->profile_photo) }}"
                alt="{{ __('Current profile photo') }}"
                class="clinic-profile-photo-preview"
            >
        @else
            <div class="clinic-profile-photo-placeholder" aria-hidden="true">
                <i class="bi bi-person-fill text-4xl"></i>
            </div>
        @endif

        <div>
            <x-input-label for="profile_photo" :value="__('Choose a profile photo')" />
            <input
                id="profile_photo"
                name="profile_photo"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
            <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Upload photo') }}</x-primary-button>

            @if (session('status') === 'profile-photo-updated')
                <p class="text-sm text-green-600">{{ __('Profile photo updated.') }}</p>
            @endif
        </div>
    </form>
</section>
