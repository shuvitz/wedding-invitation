<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Attempt to authenticate the user.
     *
     * `active => true` is folded into the credentials Auth::attempt()
     * queries against — the Eloquent user provider treats every non-password
     * key as a `where()` clause, so a deactivated account simply fails to
     * match rather than needing a separate post-auth check.
     */
    public function login(array $credentials): bool
    {
        return Auth::attempt(['active' => true, ...$credentials]);
    }

    /**
     * Log the user out.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();
    }
}
