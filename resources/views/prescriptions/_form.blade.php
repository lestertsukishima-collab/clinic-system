<form method="POST" action="{{ $editing ? route('prescriptions.update', $prescription) : route('prescriptions.store') }}" class="clinic-record-form">
    @csrf
    @if($editing) @method('PUT') @endif
    <div>
        <label for="appointment_id" class="block text-sm font-medium text-gray-700">Appointment</label>
        <select id="appointment_id" name="appointment_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">Select an appointment</option>
            @foreach($appointments as $appointment)
                <option value="{{ $appointment->id }}" @selected((string) old('appointment_id', $prescription->appointment_id) === (string) $appointment->id)>
                    #{{ $appointment->id }} — {{ $appointment->patient->name }} — {{ $appointment->doctor->display_name }} — {{ $appointment->local_appointment_date->format('M d, Y') }}
                </option>
            @endforeach
        </select>
        @error('appointment_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div><label for="diagnosis" class="block text-sm font-medium text-gray-700">Diagnosis</label><textarea id="diagnosis" name="diagnosis" rows="3" maxlength="5000" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('diagnosis', $prescription->diagnosis) }}</textarea>@error('diagnosis')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
    <div><label for="medicines" class="block text-sm font-medium text-gray-700">Medicines and dosage</label><textarea id="medicines" name="medicines" rows="5" maxlength="5000" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('medicines', $prescription->medicines) }}</textarea>@error('medicines')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
    <div><label for="instructions" class="block text-sm font-medium text-gray-700">Instructions (optional)</label><textarea id="instructions" name="instructions" rows="4" maxlength="5000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('instructions', $prescription->instructions) }}</textarea>@error('instructions')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
    <div class="clinic-form-actions"><button class="clinic-primary-button" type="submit">{{ $editing ? 'Save Changes' : 'Create Prescription' }}</button><a class="clinic-action-button" href="{{ route('prescriptions.index') }}">Cancel</a></div>
</form>
