<form action="{{ $action }}" method="POST" class="clinic-record-form">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    @if(auth()->user()->role === 'admin')
        <div>
            <label for="patient_id" class="block text-sm font-medium text-gray-700">Patient</label>
            <select id="patient_id" name="patient_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Select a patient</option>
                @foreach($patients as $patient)
                    <option value="{{ $patient->id }}" @selected((string) old('patient_id', $appointment->patient_id) === (string) $patient->id)>
                        {{ $patient->name }} ({{ $patient->email }})
                    </option>
                @endforeach
            </select>
            @error('patient_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    @endif

    <div>
        <label for="doctor_id" class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" name="doctor_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">Select a doctor</option>
            @foreach($doctors as $doctor)
                <option value="{{ $doctor->id }}" @selected((string) old('doctor_id', $appointment->doctor_id) === (string) $doctor->id)>
                    {{ $doctor->display_name }} — {{ $doctor->specialization }}
                </option>
            @endforeach
        </select>
        @error('doctor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="service_id" class="block text-sm font-medium text-gray-700">Service</label>
        <select id="service_id" name="service_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <option value="">Select a service</option>
            @foreach($services as $service)
                <option value="{{ $service->id }}" @selected((string) old('service_id', $appointment->service_id) === (string) $service->id)>
                    {{ $service->name }} (₱{{ number_format((float) $service->price, 2) }})
                </option>
            @endforeach
        </select>
        @error('service_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="appointment_date" class="block text-sm font-medium text-gray-700">Date and time ({{ config('clinic.timezone') }})</label>
        <input id="appointment_date" name="appointment_date" type="datetime-local" required
            value="{{ old('appointment_date', $appointment->local_appointment_date?->format('Y-m-d\TH:i')) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        <p class="mt-1 text-sm text-gray-600">Allow {{ config('clinic.appointment_duration_minutes') }} minutes per appointment. Overlapping bookings are unavailable.</p>
        @error('appointment_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
        <textarea id="notes" name="notes" rows="4" maxlength="5000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $appointment->notes) }}</textarea>
        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="clinic-form-actions">
        <button type="submit" class="clinic-primary-button">
            {{ $editing ? 'Save Changes' : 'Request Appointment' }}
        </button>
        <a href="{{ route('appointments.index') }}" class="clinic-action-button">Cancel</a>
    </div>
</form>
