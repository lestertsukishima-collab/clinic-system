<?php

namespace App\Http\Controllers;

use App\AppointmentWorkflow;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, AppointmentWorkflow $workflow): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $workflow->deleteAccount($user);

        Auth::logoutCurrentDevice();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $previousPhotoPath = $user->profile_photo;
        $photoPath = $request->file('profile_photo')->store('profiles', 'public');

        if ($photoPath === false) {
            return back()->withErrors([
                'profile_photo' => 'The profile photo could not be saved. Please try again.',
            ]);
        }

        $user->profile_photo = $photoPath;
        $user->save();

        if (is_string($previousPhotoPath) && $previousPhotoPath !== $photoPath) {
            Storage::disk('public')->delete($previousPhotoPath);
        }

        return back()->with('status', 'profile-photo-updated');
    }
}
