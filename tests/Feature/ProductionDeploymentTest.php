<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->app['env'] = 'production';
    config([
        'clinic.admin' => ['name' => 'Clinic Owner', 'email' => 'owner@clinic-live.org', 'password' => 'Production-test-only!'],
        'app.debug' => false,
        'app.url' => 'https://clinic-live.org',
        'app.key' => 'base64:'.base64_encode('12345678901234567890123456789012'),
        'app.cipher' => 'AES-256-CBC',
        'cache.default' => 'database',
        'session.driver' => 'database',
        'session.secure' => true,
        'session.http_only' => true,
        'session.encrypt' => true,
        'session.same_site' => 'lax',
        'mail.default' => 'smtp',
        'mail.mailers.smtp' => ['host' => 'smtp.clinic-live.org', 'port' => 587, 'scheme' => 'smtp', 'timeout' => 5, 'username' => 'mail-user', 'password' => 'Mail-test-only!'],
        'mail.from.address' => 'clinic@clinic-live.org',
    ]);
});

afterEach(function () {
    $root = realpath(storage_path('framework/testing'));
    $path = realpath(public_path());

    if ($root !== false && $path !== false && str_starts_with($path, $root.DIRECTORY_SEPARATOR.'production-deploy-')) {
        File::deleteDirectory($path);
    }
});

function prepareProductionDeploymentFiles(): void
{
    $path = storage_path('framework/testing/production-deploy-'.Str::uuid());
    app()->usePublicPath($path);
    File::ensureDirectoryExists($path.'/build');
    File::ensureDirectoryExists($path.'/storage');
    config(['filesystems.disks.public.root' => $path.'/storage']);

    $manifest = [];
    foreach (['resources/css/bootstrap.min.css', 'resources/css/app.css', 'resources/js/app.js', 'resources/js/bootstrap.bundle.min.js'] as $index => $entryPoint) {
        $file = 'asset-'.$index.'.js';
        File::put($path.'/build/'.$file, '');
        $manifest[$entryPoint] = ['file' => $file];
    }
    File::put($path.'/build/manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
}

it('seeds only a real administrator in production without sample clinic records', function () {
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('doctors', 0);
    $this->assertDatabaseCount('services', 0);
    $this->assertDatabaseCount('appointments', 0);
    $user = User::sole();
    expect($user->email)->toBe('owner@clinic-live.org');
    expect($user->role)->toBe('admin');
    expect(Hash::check('Production-test-only!', $user->password))->toBeTrue();
});

it('preserves existing administrator credentials after bootstrap secrets are removed', function () {
    $admin = User::factory()->admin()->create(['email' => 'owner@clinic-live.org', 'password' => 'Owner-changed-test-only!']);
    config(['clinic.admin.password' => null, 'clinic.admin.name' => null]);

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $this->assertDatabaseCount('users', 1);
    expect(Hash::check('Owner-changed-test-only!', $admin->fresh()->password))->toBeTrue();
});

it('replaces a known development administrator password during production setup', function (string $password) {
    $admin = User::factory()->admin()->create(['email' => 'owner@clinic-live.org', 'password' => $password]);

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(Hash::check('Production-test-only!', $admin->fresh()->password))->toBeTrue();
})->with(['password123', 'password']);

it('does not promote an existing non-administrator during production seeding', function (string $role) {
    $user = User::factory()->create(['email' => 'owner@clinic-live.org', 'role' => $role]);

    expect(fn () => app(DatabaseSeeder::class)->run())->toThrow(ValidationException::class);

    expect($user->fresh()->role)->toBe($role);
    $this->assertDatabaseCount('users', 1);
})->with(['patient', 'doctor']);

it('refuses invalid initial administrator settings before creating records', function (string $setting, mixed $value) {
    config(['clinic.admin.'.$setting => $value]);

    expect(fn () => app(DatabaseSeeder::class)->run())->toThrow(ValidationException::class);

    $this->assertDatabaseCount('users', 0);
})->with([
    'missing name' => ['name', null],
    'missing email' => ['email', null],
    'invalid email' => ['email', 'not-an-email'],
    'missing password' => ['password', null],
    'default password' => ['password', 'password123'],
    'password over bcrypt byte limit' => ['password', str_repeat('a', 73)],
    'multibyte password over bcrypt byte limit' => ['password', str_repeat('é', 40)],
]);

it('retains the local demo seeding workflow outside production', function () {
    $this->app['env'] = 'local';

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('appointments', 1);
});

it('passes production checks with complete configuration and deployment files', function () {
    prepareProductionDeploymentFiles();
    User::factory()->admin()->create(['password' => 'Admin-check-test-only!']);

    $this->artisan('clinic:check-production')
        ->expectsOutputToContain('Production configuration and deployment file checks passed.')
        ->assertSuccessful();
});

it('rejects unsafe production configuration without printing credentials', function (string $setting, mixed $value) {
    prepareProductionDeploymentFiles();
    User::factory()->admin()->create(['password' => 'Admin-check-test-only!']);
    config([$setting => $value]);

    $this->artisan('clinic:check-production')
        ->doesntExpectOutputToContain('Mail-test-only!')
        ->expectsOutputToContain('Production checks failed.')
        ->assertFailed();
})->with([
    'debugging enabled' => ['app.debug', true],
    'HTTP domain' => ['app.url', 'http://clinic-live.org'],
    'development domain' => ['app.url', 'https://clinic-system.test'],
    'placeholder domain' => ['app.url', 'https://replace-with-your-domain.invalid'],
    'reserved example domain' => ['app.url', 'https://your-domain.example'],
    'missing key' => ['app.key', ''],
    'invalid base64 key' => ['app.key', 'base64:invalid!'],
    'insecure cookies' => ['session.secure', false],
    'JavaScript-readable cookies' => ['session.http_only', false],
    'unencrypted sessions' => ['session.encrypt', false],
    'cross-site cookies' => ['session.same_site', 'none'],
    'nonpersistent sessions' => ['session.driver', 'array'],
    'nonshared appointment locks' => ['cache.default', 'array'],
    'logging emails' => ['mail.default', 'log'],
    'sandbox mail' => ['mail.mailers.smtp.host', 'sandbox.smtp.mailtrap.io'],
    'placeholder mail password' => ['mail.mailers.smtp.password', 'your_password'],
    'missing mail sender' => ['mail.from.address', ''],
    'example sender' => ['mail.from.address', 'hello@example.com'],
]);

it('rejects development environment settings', function () {
    prepareProductionDeploymentFiles();
    User::factory()->admin()->create(['password' => 'Admin-check-test-only!']);
    $this->app['env'] = 'local';

    $this->artisan('clinic:check-production')->expectsOutputToContain('APP_ENV must be production.')->assertFailed();
});

it('rejects deployment files with a missing manifest or compiled asset', function (string $file) {
    prepareProductionDeploymentFiles();
    User::factory()->admin()->create(['password' => 'Admin-check-test-only!']);
    File::delete(public_path('build/'.$file));

    $this->artisan('clinic:check-production')->expectsOutputToContain('Deploy public/build')->assertFailed();
})->with(['manifest.json', 'asset-2.js']);

it('rejects a stale Vite development hot file', function () {
    prepareProductionDeploymentFiles();
    User::factory()->admin()->create(['password' => 'Admin-check-test-only!']);
    File::put(public_path('hot'), 'http://localhost:5173');

    $this->artisan('clinic:check-production')->expectsOutputToContain('Remove the Vite development hot file')->assertFailed();
});

it('rejects a missing profile upload link', function () {
    prepareProductionDeploymentFiles();
    User::factory()->admin()->create(['password' => 'Admin-check-test-only!']);
    config(['filesystems.disks.public.root' => storage_path('app/public')]);

    $this->artisan('clinic:check-production')->expectsOutputToContain('Expose profile uploads')->assertFailed();
});

it('rejects pending database migrations', function () {
    prepareProductionDeploymentFiles();
    User::factory()->admin()->create(['password' => 'Admin-check-test-only!']);
    DB::table('migrations')->where('migration', '2026_09_07_155103_create_prescriptions_table')->delete();

    $this->artisan('clinic:check-production')->expectsOutputToContain('All application database migrations must be applied.')->assertFailed();
});

it('rejects a database without an administrator', function () {
    prepareProductionDeploymentFiles();

    $this->artisan('clinic:check-production')->expectsOutputToContain('Create your production administrator')->assertFailed();
});

it('rejects known development passwords on administrator and demo accounts', function (string $role, string $email) {
    prepareProductionDeploymentFiles();
    User::factory()->admin()->create(['password' => 'Admin-check-test-only!']);
    User::factory()->create(['role' => $role, 'email' => $email, 'password' => 'password123']);

    $this->artisan('clinic:check-production')->expectsOutputToContain('must not retain known development passwords.')->assertFailed();
})->with([
    'administrator' => ['admin', 'owner@clinic-live.org'],
    'demo doctor' => ['doctor', 'doctor@clinic.com'],
    'demo patient' => ['patient', 'patient@gmail.com'],
]);

it('reports inaccessible database configuration without exposing connection details', function () {
    prepareProductionDeploymentFiles();
    $originalConnection = config('database.default');
    config(['database.default' => 'unconfigured-connection']);

    try {
        $this->artisan('clinic:check-production')
            ->expectsOutputToContain('The production database must be reachable')
            ->doesntExpectOutputToContain('unconfigured-connection')
            ->assertFailed();
    } finally {
        config(['database.default' => $originalConnection]);
    }
});
