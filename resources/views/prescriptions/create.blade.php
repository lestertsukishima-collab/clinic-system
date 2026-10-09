<x-app-layout>
    <x-slot name="header">
        <x-record-heading
            title="Create Prescription"
            eyebrow="TREATMENT RECORD"
            :back-url="route('prescriptions.index')"
            back-label="Back to prescriptions"
        />
    </x-slot>

    <x-record-form-card title="Treatment details" description="Record the diagnosis, medicines, and instructions for this appointment." icon="bi-prescription2">
        @include('prescriptions._form', ['editing' => false])
    </x-record-form-card>
</x-app-layout>
