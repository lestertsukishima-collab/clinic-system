<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between gap-4"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Service Details</h2><a href="{{ route('services.index') }}" class="text-blue-700 underline">Back to services</a></div></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
        @if(session('success'))<p class="rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</p>@endif
        @if(session('error'))<p class="rounded bg-red-100 p-3 text-red-800">{{ session('error') }}</p>@endif
        <p><strong>Name:</strong> {{ $service->name }}</p><p><strong>Description:</strong> {{ $service->description ?: 'No description' }}</p>
        <p><strong>Price:</strong> ₱{{ number_format((float) $service->price, 2) }}</p><p><strong>Appointments:</strong> {{ $service->appointments_count }}</p>
        <div class="flex gap-3"><a href="{{ route('services.edit', $service) }}" class="rounded bg-gray-200 px-4 py-2">Edit</a>
            <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Delete this service?')">@csrf @method('DELETE')<button type="submit" class="rounded bg-red-600 px-4 py-2 text-white">Delete</button></form>
        </div>
    </div></div></div>
</x-app-layout>
