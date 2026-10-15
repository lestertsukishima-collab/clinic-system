@props(['doctor', 'unavailabilities'])

<section class="clinic-admin-panel" aria-labelledby="manage-availability-title">
    <div class="clinic-admin-panel-heading"><h2 id="manage-availability-title">Manage availability</h2></div>
    <p class="clinic-card-description">Mark when {{ $doctor->display_name }} is unavailable. Patients will see these periods on their dashboard. Existing appointments remain on the schedule with a warning. Times are in {{ config('clinic.timezone') }}.</p>
    <form method="POST" action="{{ route('doctors.availability.store', $doctor) }}" class="clinic-record-form">
        @csrf
        <div>
            <label for="starts_at" class="block text-sm font-medium text-gray-700">Unavailable from</label>
            <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label for="ends_at" class="block text-sm font-medium text-gray-700">Unavailable until</label>
            <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div class="clinic-form-actions"><button type="submit" class="clinic-primary-button">Save unavailable period</button></div>
    </form>
    @foreach($unavailabilities as $unavailability)
        <div class="clinic-detail-notes">
            <p>{{ $unavailability->starts_at->setTimezone(config('clinic.timezone'))->format('M j, Y g:i A') }} &ndash; {{ $unavailability->ends_at->setTimezone(config('clinic.timezone'))->format('M j, Y g:i A') }}</p>
            <form method="POST" action="{{ route('doctors.availability.destroy', $unavailability) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="clinic-action-button clinic-action-cancel">Remove unavailable period</button>
            </form>
        </div>
    @endforeach
</section>
