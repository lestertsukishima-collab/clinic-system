<x-app-layout>
    <x-slot name="header">
        <x-record-heading
            title="Add Doctor"
            eyebrow="CLINIC MANAGEMENT"
            :back-url="route('doctors.index')"
            back-label="Back to doctors"
        />
    </x-slot>

    <x-record-form-card title="Doctor information" description="Manage the doctor's contact information and specialization." icon="bi-person-badge">
        @include('doctors._form', ['editing' => false])
    </x-record-form-card>
</x-app-layout>
