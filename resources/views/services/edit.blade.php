<x-app-layout>
    <x-slot name="header">
        <x-record-heading
            title="Edit Clinic Service"
            eyebrow="CLINIC MANAGEMENT"
            :back-url="route('services.index')"
            back-label="Back to services"
        />
    </x-slot>

    <x-record-form-card title="Service information" description="Set the service details and price for clinic appointments." icon="bi-heart-pulse">
        @include('services._form', ['editing' => true])
    </x-record-form-card>
</x-app-layout>
