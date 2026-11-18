<?php

namespace App\Policies;

use App\Models\User;

/**
 * Admin-only (admins pass every check via Gate::before): names and phone
 * numbers are personal data, same rationale as FormSubmissionPolicy.
 */
class GuestPolicy
{
    public function manage(User $user): bool
    {
        return false;
    }
}
