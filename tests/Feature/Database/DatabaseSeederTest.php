<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

it('seeds every clinic table with related demo records without duplicates on a later day', function () {
    $this->freezeTime();

    $this->seed();
    $this->travel(1)->days();
    $this->seed();

    $this->assertDatabaseCount('users', 9);
    $this->assertDatabaseCount('doctors', 3);
    $this->assertDatabaseCount('services', 5);
    $this->assertDatabaseCount('appointments', 21);
    $this->assertDatabaseCount('prescriptions', 5);

    foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $status) {
        $this->assertDatabaseHas('appointments', ['status' => $status]);
    }

    foreach (Appointment::with(['patient', 'doctor.user', 'service'])->get() as $appointment) {
        expect($appointment->patient->role)->toBe('patient');
        expect($appointment->doctor->user->role)->toBe('doctor');
        $this->assertModelExists($appointment->service);
    }

    foreach (Prescription::with('appointment')->get() as $prescription) {
        expect($prescription->appointment->status)->toBe('completed');
    }
});

it('preserves existing and edited clinic records when seeded again', function () {
    $existingPatient = User::factory()->patient()->create();
    $this->seed();
    $doctor = Doctor::whereHas('user', fn (Builder $query): Builder => $query->where('email', 'ana.santos@clinic.com'))->firstOrFail();
    $doctor->user->update(['password' => 'Changed-doctor-password!']);
    $doctor->update(['phone' => '09999999999']);
    $service = Service::where('name', 'Follow-up Consultation')->firstOrFail();
    $service->update(['price' => 425]);
    $appointment = Appointment::where('notes', 'like', 'Demo appointment: % / pending.')->firstOrFail();
    $appointment->update(['status' => 'confirmed']);
    $prescription = Prescription::firstOrFail();
    $prescription->update(['diagnosis' => 'Updated diagnosis']);

    $this->seed();

    $this->assertModelExists($existingPatient);
    $this->assertDatabaseCount('users', 10);
    $this->assertDatabaseCount('appointments', 21);
    $this->assertDatabaseCount('prescriptions', 5);
    expect(Hash::check('Changed-doctor-password!', $doctor->user->fresh()->password))->toBeTrue();
    $this->assertDatabaseHas('doctors', ['id' => $doctor->id, 'phone' => '09999999999']);
    $this->assertDatabaseHas('services', ['id' => $service->id, 'price' => 425]);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
    $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id, 'diagnosis' => 'Updated diagnosis']);
});

it('rejects missing production passwords before writing any clinic records', function () {
    $this->app['env'] = 'production';
    config(['clinic.demo_passwords' => ['admin' => null, 'doctor' => null, 'patient' => null]]);

    expect(fn () => app(DatabaseSeeder::class)->run())->toThrow(ValidationException::class);

    foreach (['users', 'doctors', 'services', 'appointments', 'prescriptions'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
});

it('uses configured passwords for every additional demo account in production', function () {
    $this->app['env'] = 'production';
    config(['clinic.demo_passwords' => [
        'admin' => 'Demo-admin-test-only!',
        'doctor' => 'Demo-doctor-test-only!',
        'patient' => 'Demo-patient-test-only!',
    ]]);

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $this->assertDatabaseCount('users', 9);
    foreach (User::all() as $user) {
        expect(Hash::check(config("clinic.demo_passwords.{$user->role}"), $user->password))->toBeTrue();
    }
});

it('replaces local default passwords in production and preserves changed passwords', function () {
    $this->seed();
    $doctor = User::where('email', 'ana.santos@clinic.com')->firstOrFail();
    $doctor->update(['password' => 'Changed-doctor-password!']);
    $patient = User::where('email', 'jose.garcia@example.com')->firstOrFail();
    $patient->update(['password' => 'Changed-patient-password!']);
    $this->app['env'] = 'production';
    config(['clinic.demo_passwords' => [
        'admin' => 'Demo-admin-test-only!',
        'doctor' => 'Demo-doctor-test-only!',
        'patient' => 'Demo-patient-test-only!',
    ]]);

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(Hash::check('Changed-doctor-password!', $doctor->fresh()->password))->toBeTrue();
    expect(Hash::check('Changed-patient-password!', $patient->fresh()->password))->toBeTrue();
    expect(Hash::check('Demo-doctor-test-only!', User::where('email', 'miguel.reyes@clinic.com')->value('password')))->toBeTrue();
    expect(Hash::check('Demo-patient-test-only!', User::where('email', 'sofia.mendoza@example.com')->value('password')))->toBeTrue();
    $this->assertDatabaseCount('users', 9);
});
