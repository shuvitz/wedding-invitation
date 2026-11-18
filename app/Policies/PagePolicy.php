<?php

namespace App\Policies;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;

/**
 * Admins get every ability via Gate::before. For everyone else (in
 * practice, editors — the only other role):
 *
 * - viewAny/view: true. Pages aren't sensitive to look at.
 * - create: true. Editors can create pages — PageService always creates
 *   them as draft regardless of what's requested, so this can never
 *   create already-published content.
 * - update: true only while the page is still a draft. Once a page is
 *   published, only an admin may change it — otherwise an editor could
 *   silently rewrite live public content, which is the same problem as
 *   letting them publish, just approached from the other direction.
 * - publish: false. A distinct ability from `update`, checked by the
 *   dedicated status endpoint only — status is never part of the general
 *   update payload (see UpdatePageRequest/PageService), so there's no
 *   route by which an editor's create/update access can flip a page's
 *   published state.
 * - setHome: false. Same reasoning as publish — designating the site
 *   homepage is a site-wide structural decision, not routine editing.
 * - delete: false, per the brief's explicit "editor cannot delete" rule.
 */
class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Page $page): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Page $page): bool
    {
        return $page->status === PageStatus::Draft;
    }

    public function publish(User $user, Page $page): bool
    {
        return false;
    }

    public function setHome(User $user, Page $page): bool
    {
        return false;
    }

    public function delete(User $user, Page $page): bool
    {
        return false;
    }
}
