<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorUnavailability;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['clinic.timezone' => 'Asia/Manila', 'clinic.appointment_duration_minutes' => 30]);
    $this->travelTo(Carbon::parse('2026-10-15 00:00:00', 'UTC'));
});

it('lets doctors and administrators publish unavailable periods in clinic time', function (string $role) {
    $doctor = Doctor::factory()->create();
    $user = $role === 'doctor' ? $doctor->user : User::factory()->admin()->create();

    $this->actingAs($user)->from(route('dashboard'))->post(route('doctors.availability.store', $doctor), [
        'starts_at' => '2026-10-16T09:00',
        'ends_at' => '2026-10-16T10:00',
        'doctor_id' => Doctor::factory()->create()->id,
    ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('doctor_unavailabilities', [
        'doctor_id' => $doctor->id, 'starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00',
    ]);
    $this->assertDatabaseCount('doctor_unavailabilities', 1);
})->with(['doctor', 'admin']);

it('rejects unavailable periods with invalid dates without saving', function (array $input, string $error) {
    $doctor = Doctor::factory()->create();

    $this->actingAs($doctor->user)->postJson(route('doctors.availability.store', $doctor), $input)
        ->assertUnprocessable()->assertJsonValidationErrors($error);

    $this->assertDatabaseCount('doctor_unavailabilities', 0);
})->with([
    'missing times' => [[], 'starts_at'],
    'malformed time' => [['starts_at' => 'not-a-date', 'ends_at' => '2026-10-16T10:00'], 'starts_at'],
    'reversed interval' => [['starts_at' => '2026-10-16T10:00', 'ends_at' => '2026-10-16T09:00'], 'ends_at'],
    'empty interval' => [['starts_at' => '2026-10-16T09:00', 'ends_at' => '2026-10-16T09:00'], 'ends_at'],
    'expired interval' => [['starts_at' => '2026-10-14T09:00', 'ends_at' => '2026-10-14T10:00'], 'ends_at'],
]);

it('refuses changes to another doctors availability and changes by patients', function (string $role) {
    $period = DoctorUnavailability::factory()->create();
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->postJson(route('doctors.availability.store', $period->doctor), [
        'starts_at' => '2026-10-16T09:00', 'ends_at' => '2026-10-16T10:00',
    ])->assertForbidden();
    $this->deleteJson(route('doctors.availability.destroy', $period))->assertForbidden();

    $this->assertModelExists($period);
    $this->assertDatabaseCount('doctor_unavailabilities', 1);
})->with(['doctor', 'patient']);

it('requires authentication to check or change doctor availability', function () {
    $period = DoctorUnavailability::factory()->create();

    $this->post(route('doctors.availability.store', $period->doctor))->assertRedirect(route('login'));
    $this->delete(route('doctors.availability.destroy', $period))->assertRedirect(route('login'));
    $this->getJson('/api/doctors/'.$period->doctor_id.'/availability')->assertUnauthorized();

    $this->assertModelExists($period);
    $this->assertDatabaseCount('doctor_unavailabilities', 1);
});

it('blocks overlapping unavailable periods for patient and administrator bookings', function (string $endpoint, string $role, string $time) {
    $period = DoctorUnavailability::factory()->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $patient = User::factory()->patient()->create();
    $user = $role === 'patient' ? $patient : User::factory()->admin()->create();
    $service = Service::factory()->create();

    $this->actingAs($user)->postJson($endpoint, [
        'patient_id' => $patient->id, 'doctor_id' => $period->doctor_id,
        'service_id' => $service->id, 'appointment_date' => $time,
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'appointment_date' => 'This doctor is unavailable at the selected time. Please choose another time or doctor.',
    ]);

    $this->assertDatabaseCount('appointments', 0);
})->with(['/appointments', '/api/appointments'])->with(['patient', 'admin'])
    ->with(['2026-10-16T08:45', '2026-10-16T09:00', '2026-10-16T09:45', '2026-10-16T01:00:00Z']);

it('allows appointments immediately before or after an unavailable period', function (string $time) {
    $period = DoctorUnavailability::factory()->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $patient = User::factory()->patient()->create();
    $service = Service::factory()->create();

    $this->actingAs($patient)->postJson('/api/appointments', [
        'doctor_id' => $period->doctor_id, 'service_id' => $service->id, 'appointment_date' => $time,
    ])->assertCreated();

    $this->assertDatabaseHas('appointments', ['doctor_id' => $period->doctor_id, 'patient_id' => $patient->id, 'status' => 'pending']);
})->with(['2026-10-16T08:30', '2026-10-16T10:00']);

it('uses the configured visit duration when checking unavailability', function () {
    config(['clinic.appointment_duration_minutes' => 60]);
    $period = DoctorUnavailability::factory()->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $patient = User::factory()->patient()->create();

    $this->actingAs($patient)->postJson('/api/appointments', [
        'doctor_id' => $period->doctor_id, 'service_id' => Service::factory()->create()->id,
        'appointment_date' => '2026-10-16T08:15',
    ])->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseCount('appointments', 0);
});

it('preserves an existing appointment when rescheduling into an unavailable period', function (string $endpoint, string $role) {
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-17 01:00:00']);
    DoctorUnavailability::factory()->for($appointment->doctor)->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $user = $role === 'patient' ? $appointment->patient : User::factory()->admin()->create();

    $this->actingAs($user)->putJson($endpoint.'/'.$appointment->id, [
        'doctor_id' => $appointment->doctor_id, 'service_id' => $appointment->service_id,
        'appointment_date' => '2026-10-16T09:00',
    ])->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'appointment_date' => '2026-10-17 01:00:00', 'status' => 'pending']);
})->with(['/appointments', '/api/appointments'])->with(['patient', 'admin']);

it('refuses confirmation while the doctor is unavailable without sending mail', function (string $role) {
    Mail::fake();
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-16 01:00:00']);
    DoctorUnavailability::factory()->for($appointment->doctor)->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $user = $role === 'doctor' ? $appointment->doctor->user : User::factory()->admin()->create();

    $this->actingAs($user)->postJson(route('appointments.confirm', $appointment))
        ->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
    Mail::assertNothingSent();
})->with(['doctor', 'admin']);

it('lets the doctor reschedule an affected request to an available time and confirm it', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-16 01:00:00']);
    DoctorUnavailability::factory()->for($appointment->doctor)->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);

    $this->actingAs($appointment->doctor->user)->get(route('appointments.show', $appointment))
        ->assertSee('The doctor is unavailable at the scheduled time.')->assertSee('Reschedule and confirm');
    $this->post(route('appointments.confirm', $appointment), ['appointment_date' => '2026-10-16T10:00'])
        ->assertSessionHasNoErrors()->assertRedirect();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'appointment_date' => '2026-10-16 02:00:00', 'status' => 'confirmed']);
    Mail::assertSentCount(1);
});

it('removes unavailable periods and allows bookings again', function (string $role) {
    $period = DoctorUnavailability::factory()->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $doctor = $period->doctor;
    $user = $role === 'doctor' ? $doctor->user : User::factory()->admin()->create();

    $this->actingAs($user)->delete(route('doctors.availability.destroy', $period))->assertRedirect()->assertSessionHasNoErrors();
    $this->getJson(route('doctors.availability.show', ['doctor' => $doctor, 'appointment_date' => '2026-10-16T09:00']))
        ->assertExactJson(['available' => true, 'message' => null]);

    $this->assertModelMissing($period);
})->with(['doctor', 'admin']);

it('shows unavailable times and affected visits on each applicable dashboard without leaking other patients', function (string $role) {
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-16 00:45:00', 'status' => 'confirmed']);
    $period = DoctorUnavailability::factory()->for($appointment->doctor)->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $other = Appointment::factory()->create(['appointment_date' => '2026-10-16 01:00:00']);
    DoctorUnavailability::factory()->for($other->doctor)->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $expired = DoctorUnavailability::factory()->create(['starts_at' => '2026-10-14 01:00:00', 'ends_at' => '2026-10-14 02:00:00']);
    $user = match ($role) {
        'patient' => $appointment->patient,
        'doctor' => $appointment->doctor->user,
        'admin' => User::factory()->admin()->create(),
    };

    $response = $this->actingAs($user)->get(route('dashboard'))
        ->assertSee('Doctor availability notices')->assertSee($period->doctor->display_name)
        ->assertSee('Oct 16, 2026 9:00 AM')->assertSee('is unavailable for')
        ->assertSee(route('appointments.show', $appointment), false)
        ->assertDontSee($expired->doctor->display_name);

    if ($role !== 'admin') {
        $response->assertDontSee($other->patient->name)
            ->assertViewHas('affectedAppointments', fn ($appointments) => $appointments->modelKeys() === [$appointment->id]);
    }
    if ($role === 'doctor') {
        $response->assertDontSee($other->doctor->display_name)->assertSee('Save unavailable period');
    }
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
})->with(['patient', 'doctor', 'admin']);

it('lets an administrator manage availability from doctor details', function () {
    $period = DoctorUnavailability::factory()->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('doctors.show', $period->doctor))
        ->assertSee('Manage availability')->assertSee('Oct 16, 2026 9:00 AM')
        ->assertSee(route('doctors.availability.store', $period->doctor), false)
        ->assertSee(route('doctors.availability.destroy', $period), false);
});

it('reports occupied and unavailable slots without exposing appointment details', function (string $prefix, string $role) {
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-16 01:00:00']);
    $user = User::factory()->create(['role' => $role]);
    $url = $prefix === 'api' ? '/api/doctors/'.$appointment->doctor_id.'/availability' : route('doctors.availability.show', $appointment->doctor);

    $this->actingAs($user)->getJson($url.'?appointment_date=2026-10-16T09:00')
        ->assertExactJson(['available' => false, 'message' => 'This doctor already has an appointment at the selected time. Please choose another time or doctor.']);
})->with(['web', 'api'])->with(['patient', 'doctor', 'admin']);

it('ignores cancelled bookings and does not exclude another patients booking', function () {
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-16 01:00:00', 'status' => 'cancelled']);
    $patient = User::factory()->patient()->create();
    $url = route('doctors.availability.show', $appointment->doctor);

    $this->actingAs($patient)->getJson($url.'?appointment_date=2026-10-16T09:00')
        ->assertExactJson(['available' => true, 'message' => null]);
    $this->getJson($url.'?appointment_date=2026-10-16T09:00&appointment_id='.$appointment->id)->assertNotFound();
});

it('allows checking an unchanged booking but still reports newly marked unavailability', function () {
    $appointment = Appointment::factory()->create(['appointment_date' => '2026-10-16 01:00:00']);
    $url = route('doctors.availability.show', $appointment->doctor).'?appointment_date=2026-10-16T09:00&appointment_id='.$appointment->id;

    $this->actingAs($appointment->patient)->getJson($url)->assertJsonPath('available', true);
    DoctorUnavailability::factory()->for($appointment->doctor)->create(['starts_at' => '2026-10-16 01:00:00', 'ends_at' => '2026-10-16 02:00:00']);
    $this->getJson($url)->assertExactJson(['available' => false, 'message' => 'This doctor is unavailable at the selected time. Please choose another time or doctor.']);
});
