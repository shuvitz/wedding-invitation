<?php

namespace App\Policies;

use App\Models\User;

/**
 * Admins get every ability via Gate::before. The rule for everyone else,
 * documented explicitly per the Phase 2 brief:
 *
 * - Editors MAY view settings — content editors routinely need to see
 *   site-wide info (contact details, SEO defaults) while working, and
 *   there's no confidentiality concern in this data.
 * - Editors may NOT change settings — site identity/contact/SEO/social
 *   are global, site-wide configuration; there's no strong reason for a
 *   non-admin to change what the whole website presents itself as.
 */
class SettingPolicy
{
    public function view(User $user): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return false;
    }
}
