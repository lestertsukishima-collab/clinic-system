<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\TokenController;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes (Kahit hindi naka-login)
Route::get('/v1/services', function () {
    return response()->json([
        'status' => 'success',
        'data' => Service::all(),
    ], 200);
});

Route::post('/v1/auth/token', [TokenController::class, 'store'])
    ->middleware('throttle:5,1');

// Protected Routes require a valid Sanctum bearer token.
Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/v1/auth/token', [TokenController::class, 'destroy']);

    Route::get('/doctors', [DoctorController::class, 'index'])
        ->middleware('role:patient,doctor,admin');

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::match(['put', 'patch'], '/appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);
});
