<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // Public form posts, per visitor IP. Distinct key prefixes so the
        // two limits count separately.
        RateLimiter::for('form-submissions', fn (Request $request) => [
            Limit::perMinute(5)->by('minute:'.$request->ip()),
            Limit::perDay(50)->by('day:'.$request->ip()),
        ]);

        // Admins bypass every authorization check. Policies for future CMS
        // resources (Pages, Media, ...) only need to define what non-admin
        // roles are allowed to do.
        Gate::before(function (User $user, string $ability) {
            return $user->role === UserRole::Admin ? true : null;
        });
    }
}
