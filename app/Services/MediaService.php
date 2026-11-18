<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaService
{
    /**
     * Store an uploaded image and record it.
     *
     * The storage filename is never derived from the client-supplied
     * name — a UUID is generated, and its extension comes from a fixed
     * mime-type mapping (not from the original filename or a guessed
     * extension), so nothing about the stored path is attacker-controlled.
     */
    public function upload(UploadedFile $file, ?int $uploadedBy, ?string $altText = null): Media
    {
        $disk = config('media.disk', 'public');
        $extension = $this->extensionForMime($file->getMimeType());
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = 'media/'.now()->format('Y/m');

        $path = $file->storeAs($directory, $filename, $disk);

        [$width, $height] = $this->dimensions($file);

        $media = new Media();
        $media->disk = $disk;
        $media->path = $path;
        $media->original_name = $file->getClientOriginalName();
        $media->mime_type = $file->getMimeType();
        $media->size = $file->getSize();
        $media->width = $width;
        $media->height = $height;
        $media->alt_text = $altText;
        $media->uploaded_by = $uploadedBy;
        $media->save();

        return $media;
    }

    /**
     * Update the alt text — the only mutable field for V1.
     */
    public function updateAltText(Media $media, ?string $altText): Media
    {
        $media->alt_text = $altText;
        $media->save();

        return $media;
    }

    /**
     * Delete a media item and its underlying file, unless it's referenced
     * elsewhere.
     *
     * @throws ValidationException if the media is currently referenced
     */
    public function delete(Media $media): void
    {
        if ($reason = $this->referenceBlocking($media)) {
            throw ValidationException::withMessages(['media' => $reason]);
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }

    /**
     * Check whether this media is referenced anywhere that should block
     * deletion. A short, explicit list of checks — Settings' logo/favicon,
     * then every block type that can actually hold a media reference — is
     * preferable to a generic polymorphic "media attachments" table this
     * codebase doesn't need yet. As later phases add more block types (or
     * a future content type), add their check here too.
     *
     * @return string|null a human-readable reason, or null if safe to delete
     */
    public function referenceBlocking(Media $media): ?string
    {
        $identity = Setting::current()->site_identity ?? [];

        if ((int) ($identity['logo_media_id'] ?? 0) === $media->id) {
            return 'This media item is used as the site logo and cannot be deleted.';
        }

        if ((int) ($identity['favicon_media_id'] ?? 0) === $media->id) {
            return 'This media item is used as the site favicon and cannot be deleted.';
        }

        if ($page = $this->pageReferencing($media)) {
            return "This media item is used on the page \"{$page->title}\" and cannot be deleted.";
        }

        return null;
    }

    /**
     * Find a page (draft or published — both matter, a draft can be
     * published later) whose content references this media. Scans in PHP
     * rather than a JSON SQL query, since block shapes are nested and this
     * needs to behave identically on SQLite (tests) and MySQL (production)
     * — a plain loop over each page's already-decoded `content` array is
     * simpler and more portable than reasoning about JSON path queries
     * across two database engines, and at this project's scale (a small
     * site's page count) the cost is negligible.
     */
    private function pageReferencing(Media $media): ?Page
    {
        return Page::query()->get()->first(
            fn (Page $page) => collect($page->content['blocks'] ?? [])
                ->contains(fn (array $block) => $this->blockReferencesMedia($block, $media->id))
        );
    }

    /**
     * Whether a single block references this media ID. Explicit per-type
     * checks, not a generic "search for this ID anywhere in the JSON"
     * scan — mirrors BlockRegistry's own shape, and confirms rather than
     * assumes which block types can reference media at all: rich_text and
     * cta have no media field (checked directly against BlockRegistry's
     * validation rules), so they fall through to `default => false`.
     */
    private function blockReferencesMedia(array $block, int $mediaId): bool
    {
        $data = $block['data'] ?? [];

        return match ($block['type'] ?? null) {
            'hero', 'cover' => (int) ($data['image_media_id'] ?? 0) === $mediaId,
            'image' => (int) ($data['media_id'] ?? 0) === $mediaId,
            'menu', 'couple' => collect($data['items'] ?? [])->contains(fn ($item) => (int) ($item['image_media_id'] ?? 0) === $mediaId),
            'gallery' => collect($data['items'] ?? [])->contains(fn ($item) => (int) ($item['media_id'] ?? 0) === $mediaId),
            default => false,
        };
    }

    /**
     * Map a validated, content-sniffed MIME type to a safe file extension.
     * StoreMediaRequest already constrains uploads to exactly these four
     * types, so this match is exhaustive for anything that reaches here.
     */
    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'bin',
        };
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensions(UploadedFile $file): array
    {
        $info = @getimagesize($file->getRealPath());

        return $info ? [$info[0], $info[1]] : [null, null];
    }
}
