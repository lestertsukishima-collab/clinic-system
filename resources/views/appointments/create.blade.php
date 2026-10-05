<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Book an Appointment</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @include('appointments._form', [
                    'action' => route('appointments.store'),
                    'method' => 'POST',
                    'editing' => false,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
