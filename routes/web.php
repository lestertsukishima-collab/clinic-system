<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPrescriptionController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/photo', [ProfileController::class, 'updateProfile'])->name('profile.photo.update');
});

Route::middleware(['auth', 'role:patient,doctor,admin'])->group(function (): void {
    Route::resource('appointments', AppointmentController::class);
    Route::get('/appointments-list', fn () => redirect()->route('appointments.index'));
});

Route::middleware(['auth', 'role:patient,admin'])->group(function (): void {
    Route::get('/book-appointment', fn () => redirect()->route('appointments.create'));
});

Route::middleware(['auth', 'role:patient'])->group(function (): void {
    Route::resource('patient-prescriptions', PatientPrescriptionController::class)
        ->only(['index', 'show'])->parameters(['patient-prescriptions' => 'prescription']);
    Route::get('/patient-prescriptions/{prescription}/print', [PatientPrescriptionController::class, 'print'])
        ->name('patient-prescriptions.print');
});

Route::middleware(['auth', 'role:doctor,admin'])->group(function (): void {
    Route::resource('prescriptions', PrescriptionController::class);
    Route::get('/prescriptions/{prescription}/print', [PrescriptionController::class, 'print'])->name('prescriptions.print');
    Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirmAppointment'])
        ->name('appointments.confirm');
    Route::post('/appointments/{appointment}/complete', [AppointmentController::class, 'completeAppointment'])
        ->name('appointments.complete');
});

Route::middleware(['auth', 'role:admin'])->group(function (): void {
    Route::resource('patients', PatientController::class)->only(['index']);
    Route::resource('doctors', DoctorController::class);
    Route::resource('services', ServiceController::class);
});

require __DIR__.'/auth.php';
