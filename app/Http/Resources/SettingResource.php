<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * `site_identity.logo`/`favicon` are stored as Media IDs
     * (`logo_media_id`/`favicon_media_id`) — this resolves each to its
     * actual, current Storage URL rather than persisting a URL anywhere.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $identity = $this->site_identity ?? [];

        return [
            'site_identity' => [
                'name'    => $identity['name'] ?? null,
                'logo'    => $this->resolveMedia($identity['logo_media_id'] ?? null),
                'favicon' => $this->resolveMedia($identity['favicon_media_id'] ?? null),
            ],
            'contact'    => $this->contact,
            'social'     => $this->social,
            'seo'        => $this->seo,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @return array{id: int, url: string, alt_text: ?string}|null
     */
    private function resolveMedia(mixed $mediaId): ?array
    {
        if (empty($mediaId)) {
            return null;
        }

        $media = Media::find($mediaId);

        if (! $media) {
            return null;
        }

        return [
            'id'       => $media->id,
            'url'      => Storage::disk($media->disk)->url($media->path),
            'alt_text' => $media->alt_text,
        ];
    }
}
