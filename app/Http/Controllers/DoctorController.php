<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(): View
    {
        return view('doctors.index', [
            'doctors' => Doctor::with('user')->withCount('appointments')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('doctors.create', [
            'doctor' => new Doctor,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'specialization' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $doctor = DB::transaction(function () use ($validated): Doctor {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'doctor',
            ]);

            return $user->doctor()->create([
                'specialization' => $validated['specialization'],
                'phone' => $validated['phone'] ?? null,
            ]);
        });

        return redirect()->route('doctors.show', $doctor)->with('success', 'Doctor created successfully.');
    }

    public function show(Doctor $doctor): View
    {
        return view('doctors.show', [
            'doctor' => $doctor->load('user')->loadCount('appointments'),
        ]);
    }

    public function edit(Doctor $doctor): View
    {
        return view('doctors.edit', [
            'doctor' => $doctor->load('user'),
        ]);
    }

    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($doctor->user_id)],
            'specialization' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($doctor, $validated): void {
            $doctor->user()->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            $doctor->update([
                'specialization' => $validated['specialization'],
                'phone' => $validated['phone'] ?? null,
            ]);
        });

        return redirect()->route('doctors.show', $doctor)->with('success', 'Doctor updated successfully.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        $doctor->load('user');

        if ($doctor->appointments()->exists() || $doctor->user->appointments()->exists()) {
            return back()->with('error', 'This doctor has clinic records and cannot be deleted.');
        }

        DB::transaction(function () use ($doctor): void {
            $user = $doctor->user;
            $doctor->delete();
            $user->delete();
        });

        return redirect()->route('doctors.index')->with('success', 'Doctor deleted successfully.');
    }
}
