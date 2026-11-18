<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * The URL is resolved from the configured Storage disk on every
     * response rather than persisted — see Media/MediaService.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'size'          => $this->size,
            'width'         => $this->width,
            'height'        => $this->height,
            'alt_text'      => $this->alt_text,
            'url'           => Storage::disk($this->disk)->url($this->path),
            'uploaded_by'   => $this->uploaded_by,
            'uploader_name' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
