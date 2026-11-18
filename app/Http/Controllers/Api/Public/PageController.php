<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Public\PublicPageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

/**
 * The JSON counterpart to PublicPageController (Blade) — same rule (only
 * ever a published page, draft and missing are both a plain 404, no
 * information leak), different output format. Both share PageStatus and
 * BlockRegistry as the single source of truth for what "published" and
 * "a valid block" mean; neither re-derives those rules independently.
 */
class PageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('status', PageStatus::Published)
            ->firstOrFail();

        return response()->json([
            'data' => new PublicPageResource($page),
        ]);
    }

    /**
     * The designated homepage — see Page::is_home / PageService::setHome.
     * 404s if no page is currently designated (or the designated one is
     * somehow not published), rather than guessing a fallback.
     */
    public function home(): JsonResponse
    {
        $page = Page::query()
            ->where('is_home', true)
            ->where('status', PageStatus::Published)
            ->firstOrFail();

        return response()->json([
            'data' => new PublicPageResource($page),
        ]);
    }
}
