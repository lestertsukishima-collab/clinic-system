<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between gap-4"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Doctor Details</h2><a href="{{ route('doctors.index') }}" class="text-blue-700 underline">Back to doctors</a></div></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
        @if(session('success'))<p class="rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</p>@endif
        <p><strong>Name:</strong> {{ $doctor->display_name }}</p><p><strong>Email:</strong> {{ $doctor->user->email }}</p>
        <p><strong>Specialization:</strong> {{ $doctor->specialization }}</p><p><strong>Phone:</strong> {{ $doctor->phone ?: 'Not provided' }}</p>
        <p><strong>Appointments:</strong> {{ $doctor->appointments_count }}</p>
        <div class="flex gap-3"><a href="{{ route('doctors.edit', $doctor) }}" class="rounded bg-gray-200 px-4 py-2">Edit</a>
            <form method="POST" action="{{ route('doctors.destroy', $doctor) }}" onsubmit="return confirm('Delete this doctor account?')">@csrf @method('DELETE')<button type="submit" class="rounded bg-red-600 px-4 py-2 text-white">Delete</button></form>
        </div>
        @if(session('error'))<p class="rounded bg-red-100 p-3 text-red-800">{{ session('error') }}</p>@endif
    </div></div></div>
</x-app-layout>
