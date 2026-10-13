<?php

namespace Database\Seeders;

use App\Models\User;
use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductionAdminSeeder extends Seeder
{
    public function run(): void
    {
        $settings = config('clinic.admin');
        $identity = Validator::make($settings, [
            'email' => ['required', 'email', 'max:255'],
        ])->validate();
        $user = User::where('email', $identity['email'])->first();

        if ($user !== null && $user->role !== 'admin') {
            throw ValidationException::withMessages([
                'email' => 'The administrator email belongs to a non-administrator account. Use a different email.',
            ]);
        }

        if ($user !== null && ! Hash::check('password123', $user->password) && ! Hash::check('password', $user->password)) {
            return;
        }

        $validated = Validator::make($settings, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [
                'bail', 'required', 'string', 'min:12',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (strlen($value) > 72) {
                        $fail('The administrator password must not exceed 72 bytes.');
                    }
                },
            ],
        ], [
            '*.required' => 'Set CLINIC_ADMIN_NAME, CLINIC_ADMIN_EMAIL and CLINIC_ADMIN_PASSWORD for initial production setup.',
        ])->validate();

        if ($user !== null) {
            $user->update(['password' => Hash::make($validated['password'])]);

            return;
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);
    }
}
