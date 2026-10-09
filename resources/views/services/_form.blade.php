<form method="POST" action="{{ $editing ? route('services.update', $service) : route('services.store') }}" class="clinic-record-form">
    @csrf
    @if($editing) @method('PUT') @endif
    <div><label for="name" class="block text-sm font-medium text-gray-700">Name</label><input id="name" name="name" value="{{ old('name', $service->name) }}" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
    <div><label for="description" class="block text-sm font-medium text-gray-700">Description</label><textarea id="description" name="description" rows="4" maxlength="5000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('description', $service->description) }}</textarea>@error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
    <div><label for="price" class="block text-sm font-medium text-gray-700">Price (₱)</label><input id="price" name="price" type="number" min="0" max="999999.99" step="0.01" value="{{ old('price', $service->price) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">@error('price')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
    <div class="clinic-form-actions"><button class="clinic-primary-button" type="submit">{{ $editing ? 'Save Changes' : 'Create Service' }}</button><a class="clinic-action-button" href="{{ route('services.index') }}">Cancel</a></div>
</form>
