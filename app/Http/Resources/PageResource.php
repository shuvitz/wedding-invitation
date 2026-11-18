<?php

namespace App\Http\Resources;

use App\Enums\PageStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'slug'              => $this->slug,
            'status'            => $this->status?->value,
            'is_home'           => (bool) $this->is_home,
            'content'           => $this->content,
            'meta_title'        => $this->meta_title,
            'meta_description'  => $this->meta_description,
            'published_at'      => $this->published_at,
            'created_by'        => $this->created_by,
            'updated_by'        => $this->updated_by,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'public_url'        => $this->status === PageStatus::Published ? url('/'.$this->slug) : null,
        ];
    }
}
