<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Models\Media;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\View\View;

/**
 * Server-renders published Pages as plain HTML — no Vue, no client-side
 * routing, nothing the visitor's browser needs to execute to see content.
 * This is the whole point of the split: the admin SPA and the public site
 * are two different rendering paths sharing the same Laravel app and data.
 *
 * There is deliberately no draft-preview bypass here (even for logged-in
 * admins) — a draft is 404 for everyone on this route. See the Phase 4
 * report for that as a documented, revisitable simplification.
 */
class PublicPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('status', PageStatus::Published)
            ->firstOrFail();

        $setting = Setting::current();
        $identity = $setting->site_identity ?? [];

        return view('public.page', [
            'page' => $page,
            'siteName' => $identity['name'] ?? config('app.name'),
            'logoUrl' => Media::resolveUrl($identity['logo_media_id'] ?? null),
            'faviconUrl' => Media::resolveUrl($identity['favicon_media_id'] ?? null),
            'seoTitle' => $page->meta_title ?: $page->title,
            'seoDescription' => $page->meta_description ?: ($setting->seo['meta_description'] ?? null),
            'contact' => $setting->contact ?? [],
            'social' => $setting->social ?? [],
            'navigationItems' => NavigationItem::query()
                ->where('visible', true)
                ->orderBy('order')
                ->get(),
        ]);
    }
}
