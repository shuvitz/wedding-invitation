<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * Nothing is mass assignable — SettingController sets each group
     * explicitly (mirrors User's role/active convention from Phase 1),
     * keeping request data from ever writing an unexpected top-level key.
     *
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_identity' => 'array',
            'contact' => 'array',
            'social' => 'array',
            'seo' => 'array',
        ];
    }

    /**
     * Denji is single-project-per-repository — there is exactly one
     * settings record. This fetches it, creating it with empty defaults
     * on first access so the API never 404s on a fresh install.
     *
     * Built via direct property assignment rather than firstOrCreate()'s
     * mass-assignment array — $fillable is deliberately empty, so this
     * bypasses that guard the same way UserService sets `role`/`active`.
     *
     * `site_identity.logo_media_id`/`favicon_media_id` (Phase 3) store a
     * Media ID, never a filesystem path/URL — SettingResource resolves the
     * actual URL from Storage on every response. There was no production
     * data using the old plain-string `logo`/`favicon` shape (the real
     * `settings` table had zero rows when Phase 3 was implemented), so this
     * is a clean shape change, not a migration of existing values.
     */
    public static function current(): self
    {
        if ($setting = static::query()->first()) {
            return $setting;
        }

        $setting = new static();
        $setting->site_identity = ['name' => null, 'logo_media_id' => null, 'favicon_media_id' => null];
        $setting->contact = ['email' => null, 'phone' => null, 'address' => null];
        $setting->social = ['instagram' => null, 'facebook' => null, 'tiktok' => null, 'linkedin' => null, 'whatsapp' => null];
        $setting->seo = ['meta_title' => null, 'meta_description' => null];
        $setting->save();

        return $setting;
    }
}
