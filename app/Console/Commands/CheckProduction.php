<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Hash;
use Throwable;

#[Signature('clinic:check-production')]
#[Description('Check production configuration and deployment files without sending email or changing clinic records')]
class CheckProduction extends Command
{
    private int $failures = 0;

    public function handle(Migrator $migrator): int
    {
        $this->failures = 0;
        $this->check(app()->isProduction(), 'APP_ENV must be production.');
        $this->check(config('app.debug') === false, 'APP_DEBUG must be false.');
        $this->check($this->isPublicHttpsUrl((string) config('app.url')), 'APP_URL must be your public HTTPS domain.');

        $key = (string) config('app.key');
        $key = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $this->check(is_string($key) && Encrypter::supported($key, config('app.cipher')), 'APP_KEY must be a valid application encryption key.');
        $this->check(config('session.secure') === true && config('session.http_only') === true, 'Session cookies must be secure and HTTP-only.');
        $this->check(config('session.encrypt') === true, 'Session data must be encrypted.');
        $this->check(in_array(config('session.same_site'), ['lax', 'strict'], true), 'Session cookies must use SameSite lax or strict.');

        $cacheDriver = config('cache.stores.'.config('cache.default').'.driver');
        $this->check(in_array($cacheDriver, ['database', 'redis'], true), 'Use a shared database or Redis cache for appointment locks.');
        $this->check(in_array(config('session.driver'), ['database', 'redis'], true), 'Use persistent database or Redis sessions.');

        $smtp = config('mail.mailers.smtp');
        $smtpHost = (string) ($smtp['host'] ?? '');
        $smtpIsConfigured = config('mail.default') === 'smtp'
            && $this->isPublicHttpsUrl('https://'.$smtpHost)
            && ! str_contains(strtolower($smtpHost), 'sandbox')
            && in_array($smtp['scheme'] ?? null, [null, 'smtp', 'smtps'], true)
            && (int) ($smtp['port'] ?? 0) > 0
            && (int) ($smtp['port'] ?? 0) <= 65535
            && (float) ($smtp['timeout'] ?? 0) > 0
            && ! in_array($smtp['username'] ?? null, ['your_username', 'replace-me'], true)
            && ! in_array($smtp['password'] ?? null, ['your_password', 'replace-me'], true);
        $this->check($smtpIsConfigured, 'Configure a real SMTP mail service instead of logging or sandbox mail.');

        $fromAddress = (string) config('mail.from.address');
        $fromDomain = substr(strrchr($fromAddress, '@') ?: '', 1);
        $this->check(filter_var($fromAddress, FILTER_VALIDATE_EMAIL) !== false && $this->isPublicHttpsUrl('https://'.$fromDomain), 'Set a real sender email address for the mail service.');

        $this->check(! file_exists(public_path('hot')), 'Remove the Vite development hot file from the deployed public directory.');
        $this->check($this->hasBuiltAssets(), 'Deploy public/build with its manifest and every compiled asset.');

        $directories = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            app()->bootstrapPath('cache'),
            (string) config('filesystems.disks.public.root'),
        ];
        $this->check(collect($directories)->every(fn (string $path): bool => is_dir($path) && is_writable($path)), 'Storage and bootstrap/cache directories must exist and be writable.');
        $this->check(is_dir(public_path('storage')) && realpath(public_path('storage')) === realpath((string) config('filesystems.disks.public.root')), 'Expose profile uploads using php artisan storage:link.');

        $this->checkDatabase($migrator);

        if ($this->failures > 0) {
            $this->error('Production checks failed. Correct the reported settings before launch.');

            return self::FAILURE;
        }

        $this->info('Production configuration and deployment file checks passed.');
        $this->warn('Also verify live HTTPS, actual mail delivery, backups and storage persistence with your hosting provider. Check write permissions as the web-server user.');

        return self::SUCCESS;
    }

    private function checkDatabase(Migrator $migrator): void
    {
        try {
            $repository = $migrator->getRepository();

            if (! $repository->repositoryExists()) {
                $this->check(false, 'Run php artisan migrate --force on the production database.');

                return;
            }

            $files = $migrator->getMigrationFiles([database_path('migrations'), ...$migrator->paths()]);
            $this->check(array_diff(array_keys($files), $repository->getRan()) === [], 'All application database migrations must be applied.');
            $this->check(User::where('role', 'admin')->exists(), 'Create your production administrator using php artisan db:seed --force.');

            $accounts = User::where('role', 'admin')
                ->orWhereIn('email', ['admin@clinic.com', 'doctor@clinic.com', 'patient@gmail.com'])
                ->get();
            $this->check($accounts->every(fn (User $user): bool => ! Hash::check('password123', $user->password) && ! Hash::check('password', $user->password)), 'Administrator and demo accounts must not retain known development passwords.');
        } catch (Throwable) {
            $this->check(false, 'The production database must be reachable with all application tables available.');
        }
    }

    private function hasBuiltAssets(): bool
    {
        try {
            $path = public_path('build/manifest.json');

            if (! is_file($path)) {
                return false;
            }

            $manifest = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            $entryPoints = [
                'resources/css/bootstrap.min.css',
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/bootstrap.bundle.min.js',
            ];

            return is_array($manifest)
                && array_diff($entryPoints, array_keys($manifest)) === []
                && collect($manifest)->every(fn (mixed $entry): bool => is_array($entry) && isset($entry['file']) && is_string($entry['file']) && is_file(public_path('build/'.$entry['file'])));
        } catch (Throwable) {
            return false;
        }
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && $host !== ''
            && str_contains($host, '.')
            && ! in_array($host, ['localhost', 'example.com', 'www.example.com'], true)
            && ! str_ends_with($host, '.example.com')
            && ! str_ends_with($host, '.test')
            && ! str_ends_with($host, '.localhost')
            && ! str_ends_with($host, '.invalid')
            && ! str_ends_with($host, '.example')
            && (! filter_var($host, FILTER_VALIDATE_IP) || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false);
    }

    private function check(bool $passed, string $message): void
    {
        $this->line(($passed ? '<info>PASS</info> ' : '<error>FAIL</error> ').$message);

        if (! $passed) {
            $this->failures++;
        }
    }
}
