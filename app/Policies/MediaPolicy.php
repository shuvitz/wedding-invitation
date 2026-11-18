<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

/**
 * Admins get every ability via Gate::before. Editors may view/list media
 * (same rationale as Settings/Navigation — it's not sensitive) but never
 * upload, edit, or delete it.
 */
class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Media $media): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Media $media): bool
    {
        return false;
    }

    public function delete(User $user, Media $media): bool
    {
        return false;
    }
}
