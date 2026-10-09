<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || ! in_array($user->role, ['admin', 'patient'], true)) {
            return false;
        }

        $appointment = $this->route('appointment');

        if ($appointment instanceof Appointment && $user->role !== 'admin') {
            abort_unless($appointment->patient_id === $user->id, 404);
            abort_unless($appointment->status === 'pending', $this->is('api/*') ? 404 : 403);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'appointment_date' => ['bail', 'required', 'string', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->user()->role === 'admin') {
            $rules['patient_id'] = [
                ...($this->isMethod('POST') ? [] : ['sometimes']),
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'patient'),
            ];
        }

        return $rules;
    }
}
