<?php

namespace App\Policies;

use App\Models\FormSubmission;
use App\Models\User;

/**
 * Admin-only (admins pass every check via Gate::before). Unlike pages,
 * submissions are personal data typed in by the public — names, messages,
 * whatever a form asks for — so editors don't see them.
 */
class FormSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function delete(User $user, FormSubmission $submission): bool
    {
        return false;
    }
}
