<?php

namespace App\Services;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PageService
{
    /**
     * Create a page. Always starts as draft, full stop — StorePageRequest
     * doesn't even accept a `status` field, so there's no input path that
     * could create already-published content.
     */
    public function create(array $data, int $creatorId): Page
    {
        $page = new Page([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'] ?? ['blocks' => []],
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ]);

        $page->status = PageStatus::Draft;
        $page->created_by = $creatorId;
        $page->updated_by = $creatorId;
        $page->save();

        return $page;
    }

    /**
     * Update a page's title/slug/content/meta fields. Deliberately never
     * touches `status` — publishing only happens through setStatus(),
     * called by a separately-authorized controller action.
     */
    public function update(Page $page, array $data, int $updaterId): Page
    {
        foreach (['title', 'slug', 'content', 'meta_title', 'meta_description'] as $field) {
            if (array_key_exists($field, $data)) {
                $page->{$field} = $data[$field];
            }
        }

        $page->updated_by = $updaterId;
        $page->save();

        return $page;
    }

    /**
     * Change a page's publish status. `published_at` is refreshed every
     * time a page transitions (back) to published, reflecting "most
     * recently went live" rather than "first ever published". Taking a
     * page back to draft also clears `is_home` — a draft can never be the
     * live homepage, and leaving the flag set would silently make the
     * public site homepage-less without anything visibly explaining why.
     */
    public function setStatus(Page $page, PageStatus $status, int $updaterId): Page
    {
        $page->status = $status;

        if ($status === PageStatus::Published) {
            $page->published_at = now();
        } else {
            $page->is_home = false;
        }

        $page->updated_by = $updaterId;
        $page->save();

        return $page;
    }

    /**
     * Designate (or un-designate) a page as the site homepage. At most one
     * page may be home at a time — setting a new one clears the flag from
     * whichever page previously held it. Only a published page may be made
     * home, since the public homepage route only ever serves published
     * content.
     *
     * @throws ValidationException if trying to make a draft page the homepage
     */
    public function setHome(Page $page, bool $isHome, int $updaterId): Page
    {
        if ($isHome && $page->status !== PageStatus::Published) {
            throw ValidationException::withMessages([
                'is_home' => 'Only a published page can be set as the homepage.',
            ]);
        }

        DB::transaction(function () use ($page, $isHome, $updaterId) {
            if ($isHome) {
                Page::query()->where('id', '!=', $page->id)->where('is_home', true)->update(['is_home' => false]);
            }

            $page->is_home = $isHome;
            $page->updated_by = $updaterId;
            $page->save();
        });

        return $page->fresh();
    }

    public function delete(Page $page): void
    {
        $page->delete();
    }
}
