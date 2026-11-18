<?php

namespace App\Http\Resources\Public;

use App\Services\BlockRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberately excludes everything admin-only: `id`, `status` (this
 * endpoint only ever returns published pages), `created_by`/`updated_by`,
 * and raw timestamps beyond `published_at`. Block content is passed
 * through BlockRegistry::resolveForPublic() so media IDs become real URLs
 * and unwhitelisted block types are dropped — the public consumer never
 * sees a Media ID or has to resolve one itself.
 */
class PublicPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $blocks = collect($this->content['blocks'] ?? [])
            ->map(fn (array $block) => BlockRegistry::resolveForPublic($block))
            ->filter()
            ->values();

        return [
            'title'             => $this->title,
            'slug'              => $this->slug,
            'is_home'           => (bool) $this->is_home,
            'meta_title'        => $this->meta_title,
            'meta_description'  => $this->meta_description,
            'published_at'      => $this->published_at,
            'content'           => ['blocks' => $blocks],
        ];
    }
}
