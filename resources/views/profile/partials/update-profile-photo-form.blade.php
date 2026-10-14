<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Photo') }}
        </h2>

        <p id="profile-photo-help" class="mt-1 text-sm text-gray-600">
            {{ __('Choose a JPG, PNG, or WebP image up to 2 MB. Your photo will display as a circle.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6" data-profile-photo-form>
        @csrf

        <img
            @if ($user->profile_photo) src="{{ asset('storage/' . $user->profile_photo) }}" @else hidden @endif
            alt="{{ __('Profile photo preview') }}"
            class="clinic-profile-photo-preview"
            data-photo-preview
        >
        @if (! $user->profile_photo)
            <div class="clinic-profile-photo-placeholder" aria-hidden="true" data-photo-placeholder>
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
                aria-describedby="profile-photo-help profile-photo-error profile-photo-server-error"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
            <x-input-error id="profile-photo-server-error" data-photo-server-error class="mt-2" :messages="$errors->get('profile_photo')" />
            <p id="profile-photo-error" hidden data-photo-error role="alert" class="mt-2 text-sm text-red-600"></p>
            <p hidden data-photo-status role="status" class="mt-2 text-sm text-gray-600"></p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Upload photo') }}</x-primary-button>

            @if (session('status') === 'profile-photo-updated')
                <p class="text-sm text-green-600">{{ __('Profile photo updated.') }}</p>
            @endif
        </div>
    </form>
</section>
