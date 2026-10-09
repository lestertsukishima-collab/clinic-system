<form method="POST" action="{{ $editing ? route('doctors.update', $doctor) : route('doctors.store') }}" class="clinic-record-form">
    @csrf
    @if($editing) @method('PUT') @endif
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Doctor name</label>
        <input id="name" name="name" value="{{ old('name', $doctor->user?->name) }}" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $doctor->user?->email) }}" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    @unless($editing)
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Temporary password</label>
            <x-password-input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="mt-1 block w-full" />
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
            <x-password-input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="mt-1 block w-full" />
        </div>
    @endunless
    <div>
        <label for="specialization" class="block text-sm font-medium text-gray-700">Specialization</label>
        <input id="specialization" name="specialization" value="{{ old('specialization', $doctor->specialization) }}" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        @error('specialization')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">Phone (optional)</label>
        <input id="phone" name="phone" value="{{ old('phone', $doctor->phone) }}" maxlength="30" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="clinic-form-actions">
        <button class="clinic-primary-button" type="submit">{{ $editing ? 'Save Changes' : 'Create Doctor' }}</button>
        <a class="clinic-action-button" href="{{ route('doctors.index') }}">Cancel</a>
    </div>
</form>
