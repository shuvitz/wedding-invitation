<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * A manual, explicit deployment-verification step — not run automatically
 * at boot (that would be filesystem manipulation/config inspection on
 * every request for no benefit). Intended to be run once, by hand, right
 * after a deploy: `php artisan deploy:check` — see DEPLOYMENT.md.
 *
 * Checks the two things Phase B1's audit found silently break a real
 * deployment: an `APP_URL` still pointing at localhost in production (every
 * media URL is built from it), and a missing `storage:link` symlink (every
 * media URL 404s without it).
 */
class CheckDeploymentConfiguration extends Command
{
    protected $signature = 'deploy:check';

    protected $description = 'Verify APP_URL and storage configuration are production-safe';

    public function handle(): int
    {
        $failed = false;

        if (config('app.env') === 'production' && str_contains((string) config('app.url'), 'localhost')) {
            $this->error('APP_URL is set to a localhost address while APP_ENV=production. Every media URL is built from APP_URL — set it to the real public domain of this Laravel deployment.');
            $failed = true;
        } else {
            $this->info('APP_URL: OK ('.config('app.url').')');
        }

        if (config('app.env') === 'production' && str_contains((string) config('app.frontend_url'), 'localhost')) {
            $this->error('FRONTEND_URL is set to a localhost address while APP_ENV=production. Guests\' invitation links are built from it — set it to the deployed Nuxt site.');
            $failed = true;
        } else {
            $this->info('FRONTEND_URL: OK ('.config('app.frontend_url').')');
        }

        $publicLink = public_path('storage');

        if (! File::exists($publicLink)) {
            $this->error("Missing storage symlink at {$publicLink}. Run: php artisan storage:link");
            $failed = true;
        } else {
            $this->info('storage:link: OK');
        }

        $publicDisk = storage_path('app/public');

        if (! File::isDirectory($publicDisk) || ! File::isWritable($publicDisk)) {
            $this->error("{$publicDisk} does not exist or is not writable. Media uploads will fail.");
            $failed = true;
        } else {
            $this->info('storage/app/public: OK (exists and writable)');
        }

        if ($failed) {
            $this->newLine();
            $this->error('Deployment configuration check FAILED — see above.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Deployment configuration check passed.');

        return self::SUCCESS;
    }
}
