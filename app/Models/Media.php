<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;

    /**
     * `alt_text` is the only field a request ever legitimately supplies
     * directly — disk/path/original_name/mime_type/size/width/height are
     * always computed by MediaService from the uploaded file itself, never
     * trusted from request input, so they're set via direct property
     * assignment (same convention as Setting/User/NavigationItem).
     *
     * @var list<string>
     */
    protected $fillable = [
        'alt_text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Resolve a Media ID to its current Storage URL, or null if the ID is
     * empty or no longer exists. Used anywhere a stored reference (a
     * Setting field, a block's `*_media_id`) needs to become a real URL —
     * never persist the URL itself, only the ID.
     */
    public static function resolveUrl(mixed $mediaId): ?string
    {
        if (empty($mediaId)) {
            return null;
        }

        $media = static::find($mediaId);

        return $media ? Storage::disk($media->disk)->url($media->path) : null;
    }

    /**
     * Same lookup as resolveUrl(), but also returns the stored intrinsic
     * width/height so public consumers (Blade, Nuxt) can render `width`/
     * `height` attributes without a layout shift while the image loads.
     * One query instead of resolveUrl() + a separate find() for dimensions.
     *
     * @return array{url: string, width: ?int, height: ?int}|null
     */
    public static function resolveImage(mixed $mediaId): ?array
    {
        if (empty($mediaId)) {
            return null;
        }

        $media = static::find($mediaId);

        if (! $media) {
            return null;
        }

        return [
            'url' => Storage::disk($media->disk)->url($media->path),
            'width' => $media->width,
            'height' => $media->height,
        ];
    }
}
