<x-app-layout>
    <x-slot name="header">
        <x-record-heading
            title="Book an Appointment"
            eyebrow="APPOINTMENTS"
            :back-url="route('appointments.index')"
            back-label="Back to appointments"
        />
    </x-slot>

    <x-record-form-card title="Appointment details" description="Choose the doctor, service, and appointment time." icon="bi-calendar2-week">
        @include('appointments._form', [
            'action' => route('appointments.store'),
            'method' => 'POST',
            'editing' => false,
        ])
    </x-record-form-card>
</x-app-layout>
