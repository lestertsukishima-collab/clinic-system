<?php

namespace App;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentWorkflow
{
    /**
     * @param  array{doctor_id: int|string, service_id: int|string, appointment_date: string, patient_id?: int|string, notes?: string|null}  $attributes
     */
    public function schedule(User $user, array $attributes, ?Appointment $appointment = null): Appointment
    {
        return $this->synchronized(function () use ($user, $attributes, $appointment): Appointment {
            abort_unless(in_array($user->role, ['admin', 'patient'], true), 403);

            if ($appointment !== null) {
                $appointment = Appointment::query()->findOrFail($appointment->id);
                abort_unless($user->role === 'admin' || ($appointment->patient_id === $user->id && $appointment->status === 'pending'), 404);
                abort_unless(in_array($appointment->status, ['pending', 'confirmed'], true), 409);
            }

            $patientId = $user->role === 'admin'
                ? (int) ($attributes['patient_id'] ?? $appointment?->patient_id)
                : $user->id;
            $date = CarbonImmutable::parse($attributes['appointment_date'], config('clinic.timezone'))->utc();
            $this->ensureFutureDate($date);
            $this->ensureAvailable((int) $attributes['doctor_id'], $patientId, $date, $appointment?->id);

            $values = [
                'patient_id' => $patientId,
                'doctor_id' => $attributes['doctor_id'],
                'service_id' => $attributes['service_id'],
                'appointment_date' => $date,
                'notes' => $attributes['notes'] ?? null,
            ];

            if ($appointment === null) {
                return Appointment::create([...$values, 'status' => 'pending']);
            }

            $appointment->update($values);

            return $appointment;
        });
    }

    public function confirm(User $user, Appointment $appointment, ?string $appointmentDate = null): Appointment
    {
        return $this->synchronized(function () use ($user, $appointment, $appointmentDate): Appointment {
            $appointment = Appointment::query()->findOrFail($appointment->id);
            abort_unless($user->role === 'admin' || ($user->role === 'doctor' && Doctor::whereKey($appointment->doctor_id)->where('user_id', $user->id)->exists()), 403);
            abort_unless($appointment->status === 'pending', 409);

            $date = $appointmentDate === null
                ? CarbonImmutable::instance($appointment->appointment_date)
                : CarbonImmutable::parse($appointmentDate, config('clinic.timezone'))->utc();
            $this->ensureFutureDate($date);
            $this->ensureAvailable($appointment->doctor_id, $appointment->patient_id, $date, $appointment->id);
            $appointment->update([
                'status' => 'confirmed',
                ...($appointmentDate === null ? [] : ['appointment_date' => $date]),
            ]);

            return $appointment->load('patient');
        });
    }

    public function complete(User $user, Appointment $appointment): Appointment
    {
        return $this->synchronized(function () use ($user, $appointment): Appointment {
            $appointment = Appointment::query()->findOrFail($appointment->id);
            abort_unless($user->role === 'admin' || ($user->role === 'doctor' && Doctor::whereKey($appointment->doctor_id)->where('user_id', $user->id)->exists()), 403);
            abort_unless($appointment->status === 'confirmed', 409);

            if ($appointment->appointment_date->isFuture()) {
                throw ValidationException::withMessages([
                    'appointment_date' => 'A visit cannot be completed before its scheduled start time.',
                ]);
            }

            $appointment->update(['status' => 'completed']);

            return $appointment;
        });
    }

    public function cancel(User $user, Appointment $appointment): Appointment
    {
        return $this->synchronized(function () use ($user, $appointment): Appointment {
            $appointment = Appointment::query()->findOrFail($appointment->id);
            abort_unless($user->role === 'admin' || ($user->role === 'patient' && $appointment->patient_id === $user->id && $appointment->status === 'pending'), 404);
            abort_if($appointment->status === 'completed', 409);
            $appointment->update(['status' => 'cancelled']);

            return $appointment;
        });
    }

    public function deleteAccount(User $user): void
    {
        $this->synchronized(function () use ($user): void {
            if ($user->role === 'admin' && ! User::where('role', 'admin')->where('id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'account' => 'The last administrator account cannot be deleted. Another administrator must be in place first.',
                ])->errorBag('userDeletion');
            }

            if ($user->appointments()->exists() || $user->doctor()->whereHas('appointments')->exists()) {
                throw ValidationException::withMessages([
                    'account' => 'Your account has clinic records and cannot be deleted. Please contact the clinic administrator.',
                ])->errorBag('userDeletion');
            }

            $user->delete();
        }, 'userDeletion');
    }

    private function ensureFutureDate(CarbonImmutable $date): void
    {
        if (! $date->isFuture()) {
            throw ValidationException::withMessages([
                'appointment_date' => 'The appointment date must be a date after now.',
            ]);
        }
    }

    private function ensureAvailable(int $doctorId, int $patientId, CarbonImmutable $date, ?int $appointmentId): void
    {
        $duration = config('clinic.appointment_duration_minutes');
        $hasConflict = Appointment::query()
            ->where('status', '!=', 'cancelled')
            ->when($appointmentId !== null, fn (Builder $query) => $query->where('id', '!=', $appointmentId))
            ->where('appointment_date', '>', $date->subMinutes($duration))
            ->where('appointment_date', '<', $date->addMinutes($duration))
            ->where(fn (Builder $query) => $query->where('doctor_id', $doctorId)->orWhere('patient_id', $patientId))
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'appointment_date' => 'This appointment overlaps an existing booking for the doctor or patient. Please choose another time.',
            ]);
        }
    }

    private function synchronized(Closure $operation, string $errorBag = 'default'): mixed
    {
        try {
            return Cache::lock('clinic:appointment-workflows', 30)->block(5, fn () => DB::transaction($operation, 3));
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                $errorBag === 'userDeletion' ? 'account' : 'appointment_date' => 'Another clinic request is being processed. Please try again.',
            ])->errorBag($errorBag);
        }
    }
}
