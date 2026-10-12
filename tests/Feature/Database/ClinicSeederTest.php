<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

it('seeds one reusable clinic demo dataset without duplicating records', function () {
    $this->seed(ClinicSeeder::class);
    $this->seed(ClinicSeeder::class);

    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('doctors', 1);
    $this->assertDatabaseCount('services', 1);
    $this->assertDatabaseCount('appointments', 1);

    expect(User::where('email', 'doctor@clinic.com')->value('role'))->toBe('doctor')
        ->and(Doctor::with('user')->first()->user->role)->toBe('doctor')
        ->and(Service::first()->appointments)->toHaveCount(1)
        ->and(Appointment::with(['patient', 'doctor.user', 'service'])->first()->patient->role)->toBe('patient');
});

it('refuses production demo seeding before writing records when a password is invalid', function (string $role, mixed $password) {
    $this->app['env'] = 'production';
    config(['clinic.demo_passwords' => [
        'admin' => 'Demo-admin-test-only!',
        'doctor' => 'Demo-doctor-test-only!',
        'patient' => 'Demo-patient-test-only!',
    ]]);
    config(["clinic.demo_passwords.{$role}" => $password]);

    expect(fn () => app(ClinicSeeder::class)->run())->toThrow(ValidationException::class);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('doctors', 0);
    $this->assertDatabaseCount('services', 0);
    $this->assertDatabaseCount('appointments', 0);
})->with(['admin', 'doctor', 'patient'])->with([
    'missing' => null,
    'legacy default' => 'password123',
    'longer than bcrypt supports' => str_repeat('a', 73),
    'multibyte password exceeds bcrypt byte limit' => str_repeat('😀', 20),
    'not a string' => 123456789012,
]);

it('uses configured hashed passwords for production demo accounts without duplicating records', function () {
    $this->app['env'] = 'production';
    config(['clinic.demo_passwords' => [
        'admin' => 'Demo-admin-test-only!',
        'doctor' => 'Demo-doctor-test-only!',
        'patient' => 'Demo-patient-test-only!',
    ]]);

    $this->artisan('db:seed', ['--class' => ClinicSeeder::class, '--force' => true])->assertSuccessful();
    $this->artisan('db:seed', ['--class' => ClinicSeeder::class, '--force' => true])->assertSuccessful();

    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('appointments', 1);

    foreach (User::all() as $user) {
        expect(Hash::check(config("clinic.demo_passwords.{$user->role}"), $user->password))->toBeTrue();
        expect(Hash::check('password123', $user->password))->toBeFalse();
    }
});

it('replaces legacy production demo passwords while preserving passwords users already changed', function () {
    $this->seed(ClinicSeeder::class);
    User::where('role', 'doctor')->first()->update(['password' => 'Doctor-changed-test-only!']);
    $this->app['env'] = 'production';
    config(['clinic.demo_passwords' => [
        'admin' => 'Demo-admin-test-only!',
        'doctor' => 'Demo-doctor-test-only!',
        'patient' => 'Demo-patient-test-only!',
    ]]);

    $this->artisan('db:seed', ['--class' => ClinicSeeder::class, '--force' => true])->assertSuccessful();

    expect(Hash::check('Demo-admin-test-only!', User::where('role', 'admin')->first()->password))->toBeTrue();
    expect(Hash::check('Demo-patient-test-only!', User::where('role', 'patient')->first()->password))->toBeTrue();
    expect(Hash::check('Doctor-changed-test-only!', User::where('role', 'doctor')->first()->password))->toBeTrue();
    $this->assertDatabaseCount('users', 3);
});
