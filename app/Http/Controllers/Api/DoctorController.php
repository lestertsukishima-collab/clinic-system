<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;

class DoctorController extends Controller
{
    public function index(): JsonResponse
    {
        $doctors = Doctor::query()
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(fn (Doctor $doctor): array => [
                'id' => $doctor->id,
                'name' => $doctor->display_name,
                'specialization' => $doctor->specialization,
                'phone' => $doctor->phone,
            ]);

        return response()->json([
            'success' => true,
            'doctors' => $doctors,
        ]);
    }
}
