<?php

namespace App\Policies;

use App\Models\User;

/**
 * Reference policy for Denji CMS authorization.
 *
 * Admins are granted every ability automatically via the Gate::before hook
 * in AppServiceProvider — every method here only ever runs for a non-admin,
 * so returning false is the entire rule: "editor cannot access user
 * management." Future CMS resource policies (Pages, Media, ...) should
 * follow this same shape: admin bypass is free, only the non-admin rule
 * needs to be written here.
 *
 * There is deliberately no "view/update yourself" exception. Phase 0 had
 * one, but Phase 1's update endpoint also changes `role` — allowing an
 * editor to pass this check for their own account would let them call
 * PATCH /api/users/{self} and self-promote to admin. Self-service profile
 * editing (a user changing their own name/password without going through
 * this admin-only policy) is intentionally out of scope for Phase 1.
 */
class UserPolicy
{
    /**
     * Only admins may list users.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Only admins may view another user's details.
     */
    public function view(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Only admins may create users.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Only admins may update users.
     */
    public function update(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Non-admins may never delete a user (admins bypass this via Gate::before).
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }
}
