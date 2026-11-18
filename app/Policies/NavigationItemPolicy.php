<?php

namespace App\Policies;

use App\Models\NavigationItem;
use App\Models\User;

/**
 * Admins get every ability via Gate::before. Editors may view the
 * navigation (it's just a menu, not sensitive) but never modify it —
 * navigation is site-wide structure, same rationale as SettingPolicy.
 */
class NavigationItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, NavigationItem $item): bool
    {
        return false;
    }

    public function delete(User $user, NavigationItem $item): bool
    {
        return false;
    }
}
