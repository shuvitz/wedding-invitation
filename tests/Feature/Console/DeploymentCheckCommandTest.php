<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `php artisan deploy:check` (added in Phase B1) is a manual, explicit
 * verification step run by hand after a deploy — never automatically at
 * boot. It only reads filesystem/config state, never modifies it.
 */
class DeploymentCheckCommandTest extends TestCase
{
    public function test_it_passes_when_configuration_is_valid(): void
    {
        config(['app.env' => 'production', 'app.url' => 'https://example.com']);

        $files = File::partialMock();
        $files->shouldReceive('exists')->with(public_path('storage'))->andReturn(true);
        $files->shouldReceive('isDirectory')->with(storage_path('app/public'))->andReturn(true);
        $files->shouldReceive('isWritable')->with(storage_path('app/public'))->andReturn(true);

        $this->artisan('deploy:check')->assertExitCode(0);
    }

    public function test_it_fails_when_app_url_is_localhost_in_production(): void
    {
        config(['app.env' => 'production', 'app.url' => 'http://localhost']);

        $files = File::partialMock();
        $files->shouldReceive('exists')->with(public_path('storage'))->andReturn(true);
        $files->shouldReceive('isDirectory')->with(storage_path('app/public'))->andReturn(true);
        $files->shouldReceive('isWritable')->with(storage_path('app/public'))->andReturn(true);

        $this->artisan('deploy:check')
            ->expectsOutputToContain('APP_URL is set to a localhost address')
            ->assertExitCode(1);
    }

    public function test_it_allows_a_localhost_app_url_outside_production(): void
    {
        config(['app.env' => 'local', 'app.url' => 'http://localhost']);

        $files = File::partialMock();
        $files->shouldReceive('exists')->with(public_path('storage'))->andReturn(true);
        $files->shouldReceive('isDirectory')->with(storage_path('app/public'))->andReturn(true);
        $files->shouldReceive('isWritable')->with(storage_path('app/public'))->andReturn(true);

        $this->artisan('deploy:check')->assertExitCode(0);
    }

    public function test_it_fails_when_the_storage_symlink_is_missing(): void
    {
        config(['app.env' => 'production', 'app.url' => 'https://example.com']);

        $files = File::partialMock();
        $files->shouldReceive('exists')->with(public_path('storage'))->andReturn(false);
        $files->shouldReceive('isDirectory')->with(storage_path('app/public'))->andReturn(true);
        $files->shouldReceive('isWritable')->with(storage_path('app/public'))->andReturn(true);

        $this->artisan('deploy:check')
            ->expectsOutputToContain('Missing storage symlink')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_the_public_disk_is_not_writable(): void
    {
        config(['app.env' => 'production', 'app.url' => 'https://example.com']);

        $files = File::partialMock();
        $files->shouldReceive('exists')->with(public_path('storage'))->andReturn(true);
        $files->shouldReceive('isDirectory')->with(storage_path('app/public'))->andReturn(true);
        $files->shouldReceive('isWritable')->with(storage_path('app/public'))->andReturn(false);

        $this->artisan('deploy:check')
            ->expectsOutputToContain('is not writable')
            ->assertExitCode(1);
    }
}
