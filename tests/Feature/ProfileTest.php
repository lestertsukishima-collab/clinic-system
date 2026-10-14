<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an existing profile photo can be replaced for every role', function (string $extension, string $role) {
    Storage::fake('public');
    $previousPhotoPath = UploadedFile::fake()->image('previous.jpg')->store('profiles', 'public');
    $user = User::factory()->{$role}()->create(['profile_photo' => $previousPhotoPath]);
    $replacement = UploadedFile::fake()->image('replacement.'.$extension);

    $response = $this->actingAs($user)->from('/profile')->post('/profile/photo', [
        'profile_photo' => $replacement,
    ]);

    $response->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'profile-photo-updated')
        ->assertRedirect('/profile');
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'profile_photo' => 'profiles/'.$replacement->hashName(),
    ]);
    Storage::disk('public')->assertExists('profiles/'.$replacement->hashName());
    Storage::disk('public')->assertMissing($previousPhotoPath);
    $this->get('/profile')->assertSee('storage/profiles/'.$replacement->hashName(), false);
})->with(['jpg', 'jpeg', 'png', 'webp'])->with(['admin', 'doctor', 'patient']);

test('an oversized replacement preserves the existing profile photo for every role', function (string $role) {
    Storage::fake('public');
    $previousPhotoPath = UploadedFile::fake()->image('previous.jpg')->store('profiles', 'public');
    $user = User::factory()->{$role}()->create(['profile_photo' => $previousPhotoPath]);

    $response = $this->actingAs($user)->from('/profile')->post('/profile/photo', [
        'profile_photo' => UploadedFile::fake()->image('replacement.jpg')->size(2049),
    ]);

    $response->assertSessionHasErrors([
        'profile_photo' => 'The photo must not be larger than 2 MB.',
    ])->assertRedirect('/profile');
    $this->assertDatabaseHas('users', ['id' => $user->id, 'profile_photo' => $previousPhotoPath]);
    Storage::disk('public')->assertExists($previousPhotoPath);
    Storage::disk('public')->assertCount('profiles', 1);
})->with(['admin', 'doctor', 'patient']);

test('a profile photo at exactly 2 MB is accepted for every role', function (string $role) {
    Storage::fake('public');
    $user = User::factory()->{$role}()->create();
    $photo = UploadedFile::fake()->image('avatar.jpg')->size(2048);

    $response = $this->actingAs($user)->post('/profile/photo', ['profile_photo' => $photo]);

    $response->assertSessionHasNoErrors()->assertSessionHas('status', 'profile-photo-updated');
    $this->assertDatabaseHas('users', ['id' => $user->id, 'profile_photo' => 'profiles/'.$photo->hashName()]);
    Storage::disk('public')->assertExists('profiles/'.$photo->hashName());
})->with(['admin', 'doctor', 'patient']);

test('a first profile photo is displayed in the profile and sidebar for every role', function (string $role) {
    Storage::fake('public');
    $user = User::factory()->{$role}()->create();
    $photo = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->actingAs($user)->from('/profile')->post('/profile/photo', ['profile_photo' => $photo]);

    $response->assertSessionHasNoErrors()->assertSessionHas('status', 'profile-photo-updated');
    Storage::disk('public')->assertExists('profiles/'.$photo->hashName());
    $this->assertDatabaseHas('users', ['id' => $user->id, 'profile_photo' => 'profiles/'.$photo->hashName()]);
    $this->get('/profile')->assertSee('data-profile-photo-form', false)
        ->assertSee('storage/profiles/'.$photo->hashName(), false);
    $this->get('/dashboard')->assertSee('storage/profiles/'.$photo->hashName(), false);
})->with(['admin', 'doctor', 'patient']);

test('a PHP upload failure shows an actionable error and preserves the current photo', function () {
    Storage::fake('public');
    $previousPhotoPath = UploadedFile::fake()->image('previous.jpg')->store('profiles', 'public');
    $user = User::factory()->patient()->create(['profile_photo' => $previousPhotoPath]);
    $failedPhoto = new UploadedFile('', 'large.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

    $response = $this->actingAs($user)->from('/profile')->post('/profile/photo', ['profile_photo' => $failedPhoto]);

    $response->assertSessionHasErrors([
        'profile_photo' => 'The photo could not be uploaded. Choose a JPG, PNG, or WebP image up to 2 MB and try again.',
    ])->assertRedirect('/profile');
    $this->assertDatabaseHas('users', ['id' => $user->id, 'profile_photo' => $previousPhotoPath]);
    Storage::disk('public')->assertExists($previousPhotoPath);
    Storage::disk('public')->assertCount('profiles', 1);
});

test('unsupported profile photos are rejected without saving files', function (string $extension) {
    Storage::fake('public');
    $user = User::factory()->patient()->create();

    $response = $this->actingAs($user)->from('/profile')->post('/profile/photo', [
        'profile_photo' => UploadedFile::fake()->image('avatar.'.$extension),
    ]);

    $response->assertSessionHasErrors(['profile_photo' => 'Choose a JPG, PNG, or WebP image.']);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'profile_photo' => null]);
    Storage::disk('public')->assertDirectoryEmpty('profiles');
})->with(['gif', 'bmp']);

test('uploading a photo cannot change another account', function () {
    Storage::fake('public');
    $user = User::factory()->patient()->create();
    $otherUser = User::factory()->admin()->create();
    $photo = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->actingAs($user)->post('/profile/photo', [
        'profile_photo' => $photo,
        'user_id' => $otherUser->id,
        'role' => 'admin',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'patient', 'profile_photo' => 'profiles/'.$photo->hashName()]);
    $this->assertDatabaseHas('users', ['id' => $otherUser->id, 'profile_photo' => null]);
});

test('guests cannot upload profile photos', function () {
    Storage::fake('public');

    $response = $this->post('/profile/photo', ['profile_photo' => UploadedFile::fake()->image('avatar.jpg')]);

    $response->assertRedirectToRoute('login');
    Storage::disk('public')->assertDirectoryEmpty('profiles');
});

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
