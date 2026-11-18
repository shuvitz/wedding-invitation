<?php

namespace App\Services;

use App\Enums\BlockType;
use App\Models\Media;
use Illuminate\Validation\Rule;

/**
 * The single source of truth for what a Page's content blocks can be.
 *
 * This is deliberately a plain match()-based mapping, not a plugin
 * framework or dynamic class loader — adding a block type means adding one
 * case here (validation rules + Blade view), nowhere else. `view()`
 * returning null for anything not in BlockType is what makes unknown block
 * types "fail safely": the renderer simply skips them instead of trying to
 * resolve/execute arbitrary user-controlled input as code.
 */
class BlockRegistry
{
    public const FORM_FIELD_TYPES = ['text', 'textarea', 'number', 'select'];

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_map(fn (BlockType $type) => $type->value, BlockType::cases());
    }

    /**
     * Validation rules for a single block's `data`, keyed by full dot-path
     * (e.g. "content.blocks.0.data.title"). Returns [] for an unwhitelisted
     * type — the caller separately validates `type` itself against
     * self::types(), so an unknown type is already rejected before this
     * would ever matter.
     *
     * URLs (cta.url) are restricted to relative paths or http(s) — this is
     * what actually blocks `javascript:`/`data:`/etc. schemes; Blade's
     * `{{ }}` escaping does not protect attribute values from a malicious
     * scheme, only from markup injection.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(?string $type, string $prefix): array
    {
        return match (BlockType::tryFrom((string) $type)) {
            BlockType::Hero => [
                "{$prefix}.title" => ['required', 'string', 'max:255'],
                "{$prefix}.subtitle" => ['nullable', 'string', 'max:500'],
                "{$prefix}.image_media_id" => ['nullable', 'integer', 'exists:media,id'],
            ],
            BlockType::RichText => [
                "{$prefix}.title" => ['nullable', 'string', 'max:255'],
                "{$prefix}.body" => ['required', 'string', 'max:20000'],
            ],
            BlockType::Image => [
                "{$prefix}.media_id" => ['required', 'integer', 'exists:media,id'],
                "{$prefix}.alt_text" => ['nullable', 'string', 'max:255'],
            ],
            BlockType::Cta => [
                "{$prefix}.title" => ['required', 'string', 'max:255'],
                "{$prefix}.description" => ['nullable', 'string', 'max:500'],
                "{$prefix}.button_label" => ['required', 'string', 'max:100'],
                "{$prefix}.url" => ['required', 'string', 'max:2048', 'regex:/^(\/[^\s]*|https?:\/\/[^\s]+)$/i'],
            ],
            // Repeating items validate via Laravel's native `items.*.field`
            // wildcard — it already validates every entry in the array
            // regardless of count, so no extra iteration is needed beyond
            // the block-level loop the caller (Store/UpdatePageRequest)
            // already does.
            BlockType::Menu => [
                "{$prefix}.title" => ['nullable', 'string', 'max:255'],
                "{$prefix}.items" => ['nullable', 'array'],
                "{$prefix}.items.*.name" => ['required', 'string', 'max:255'],
                "{$prefix}.items.*.description" => ['nullable', 'string', 'max:500'],
                "{$prefix}.items.*.price" => ['required', 'string', 'max:50'],
                "{$prefix}.items.*.image_media_id" => ['nullable', 'integer', 'exists:media,id'],
            ],
            BlockType::Gallery => [
                "{$prefix}.title" => ['nullable', 'string', 'max:255'],
                "{$prefix}.items" => ['nullable', 'array'],
                "{$prefix}.items.*.media_id" => ['required', 'integer', 'exists:media,id'],
                "{$prefix}.items.*.alt_text" => ['nullable', 'string', 'max:255'],
            ],
            // Dates/times are plain wall-clock values (no timezone) — the
            // event's local time as printed on an invitation.
            BlockType::Schedule => [
                "{$prefix}.title" => ['nullable', 'string', 'max:255'],
                "{$prefix}.items" => ['nullable', 'array'],
                "{$prefix}.items.*.title" => ['required', 'string', 'max:255'],
                "{$prefix}.items.*.date" => ['required', 'date_format:Y-m-d'],
                "{$prefix}.items.*.start_time" => ['nullable', 'date_format:H:i'],
                "{$prefix}.items.*.end_time" => ['nullable', 'date_format:H:i'],
                "{$prefix}.items.*.venue" => ['nullable', 'string', 'max:255'],
                "{$prefix}.items.*.address" => ['nullable', 'string', 'max:500'],
                "{$prefix}.items.*.maps_url" => ['nullable', 'string', 'max:2048', 'regex:/^https?:\/\/[^\s]+$/i'],
            ],
            // `form_key` goes in the public submit URL, so it's slug-shaped.
            // Field names must start with a letter, which keeps them from
            // ever colliding with the `_hp` honeypot the endpoint reserves.
            BlockType::Form => [
                "{$prefix}.form_key" => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
                "{$prefix}.title" => ['nullable', 'string', 'max:255'],
                "{$prefix}.submit_label" => ['nullable', 'string', 'max:100'],
                "{$prefix}.success_message" => ['nullable', 'string', 'max:500'],
                "{$prefix}.fields" => ['required', 'array', 'min:1', 'max:20'],
                "{$prefix}.fields.*.name" => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct'],
                "{$prefix}.fields.*.label" => ['required', 'string', 'max:255'],
                "{$prefix}.fields.*.type" => ['required', Rule::in(self::FORM_FIELD_TYPES)],
                "{$prefix}.fields.*.required" => ['sometimes', 'boolean'],
                "{$prefix}.fields.*.options" => ["required_if:{$prefix}.fields.*.type,select", 'nullable', 'array', 'max:50'],
                "{$prefix}.fields.*.options.*" => ['required', 'string', 'max:255'],
            ],
            default => [],
        };
    }

    /**
     * The Blade view that renders this block type, or null if the type
     * isn't whitelisted — the public renderer skips anything null rather
     * than guessing a view name or loading a class from user input.
     */
    public static function view(?string $type): ?string
    {
        return match (BlockType::tryFrom((string) $type)) {
            BlockType::Hero => 'public.blocks.hero',
            BlockType::RichText => 'public.blocks.rich-text',
            BlockType::Image => 'public.blocks.image',
            BlockType::Cta => 'public.blocks.cta',
            BlockType::Menu => 'public.blocks.menu',
            BlockType::Gallery => 'public.blocks.gallery',
            BlockType::Schedule => 'public.blocks.schedule',
            // shortcut: the zero-JS Blade baseline skips forms (submitting
            // needs a CSRF-protected POST + redirect flow of its own) — only
            // Nuxt renders them. Add a Blade view + web route if a site
            // ever serves forms from the Blade renderer.
            BlockType::Form => null,
            default => null,
        };
    }

    /**
     * Transform one stored block into the shape a public API consumer
     * (e.g. the Nuxt frontend) should receive: media IDs resolved to real
     * URLs, and nothing beyond what that block type actually needs. Returns
     * null for an unwhitelisted type, so callers (PublicPageResource) can
     * simply filter nulls out — the same fail-safe behavior as view().
     *
     * This is the JSON-API equivalent of the Blade block partials
     * (resources/views/public/blocks/*) — same source-of-truth block
     * shapes, different output format. Neither duplicates Laravel's
     * validation rules; both only ever render already-validated content.
     *
     * @return array{type: string, data: array<string, mixed>}|null
     */
    public static function resolveForPublic(array $block): ?array
    {
        $type = BlockType::tryFrom((string) ($block['type'] ?? ''));
        $data = $block['data'] ?? [];

        return match ($type) {
            BlockType::Hero => ['type' => 'hero', 'data' => [
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                ...self::resolveImageFields($data['image_media_id'] ?? null, 'image_url'),
            ]],
            BlockType::RichText => ['type' => 'rich_text', 'data' => [
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
            ]],
            BlockType::Image => ['type' => 'image', 'data' => [
                ...self::resolveImageFields($data['media_id'] ?? null, 'url'),
                'alt_text' => $data['alt_text'] ?? null,
            ]],
            BlockType::Cta => ['type' => 'cta', 'data' => [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'button_label' => $data['button_label'] ?? null,
                'url' => $data['url'] ?? null,
            ]],
            BlockType::Menu => ['type' => 'menu', 'data' => [
                'title' => $data['title'] ?? null,
                'items' => array_values(array_map(fn (array $item) => [
                    'name' => $item['name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'price' => $item['price'] ?? null,
                    ...self::resolveImageFields($item['image_media_id'] ?? null, 'image_url'),
                ], $data['items'] ?? [])),
            ]],
            BlockType::Gallery => ['type' => 'gallery', 'data' => [
                'title' => $data['title'] ?? null,
                'items' => array_values(array_map(fn (array $item) => [
                    ...self::resolveImageFields($item['media_id'] ?? null, 'url'),
                    'alt_text' => $item['alt_text'] ?? null,
                ], $data['items'] ?? [])),
            ]],
            BlockType::Schedule => ['type' => 'schedule', 'data' => [
                'title' => $data['title'] ?? null,
                'items' => array_values(array_map(fn (array $item) => [
                    'title' => $item['title'] ?? null,
                    'date' => $item['date'] ?? null,
                    'start_time' => $item['start_time'] ?? null,
                    'end_time' => $item['end_time'] ?? null,
                    'venue' => $item['venue'] ?? null,
                    'address' => $item['address'] ?? null,
                    'maps_url' => $item['maps_url'] ?? null,
                ], $data['items'] ?? [])),
            ]],
            BlockType::Form => ['type' => 'form', 'data' => [
                'form_key' => $data['form_key'] ?? null,
                'title' => $data['title'] ?? null,
                'submit_label' => $data['submit_label'] ?? null,
                'success_message' => $data['success_message'] ?? null,
                'fields' => array_values(array_map(fn (array $field) => [
                    'name' => $field['name'] ?? null,
                    'label' => $field['label'] ?? null,
                    'type' => $field['type'] ?? null,
                    'required' => (bool) ($field['required'] ?? false),
                    'options' => array_values($field['options'] ?? []),
                ], $data['fields'] ?? [])),
            ]],
            default => null,
        };
    }

    /**
     * Rule for a page's whole `content.blocks` array: no two `form` blocks
     * may share a `form_key`, since it's their public submit URL. (A
     * `distinct` rule on `content.blocks.*.data.form_key` doesn't work here
     * — it collides with the per-index rules rulesFor() adds.)
     */
    public static function uniqueFormKeysRule(): \Closure
    {
        return function (string $attribute, mixed $blocks, \Closure $fail) {
            $keys = collect(is_array($blocks) ? $blocks : [])
                ->filter(fn ($block) => is_array($block) && ($block['type'] ?? null) === BlockType::Form->value)
                ->map(fn (array $block) => $block['data']['form_key'] ?? null)
                ->filter();

            if ($keys->count() !== $keys->unique()->count()) {
                $fail('Each form block on a page needs its own form key.');
            }
        };
    }

    /**
     * Validation rules for one public submission of a `form` block, built
     * from that block's own (already-validated) field definitions. Keyed by
     * field name only, so `$request->validate()` returns exactly the
     * defined fields and silently drops anything else the client sent.
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, array<int, mixed>>
     */
    public static function submissionRules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $rules[$field['name']] = [
                ($field['required'] ?? false) ? 'required' : 'nullable',
                ...match ($field['type'] ?? null) {
                    'textarea' => ['string', 'max:5000'],
                    'number' => ['numeric', 'between:-1000000000,1000000000'],
                    'select' => ['string', Rule::in($field['options'] ?? [])],
                    default => ['string', 'max:255'],
                },
            ];
        }

        return $rules;
    }

    /**
     * Resolve a media reference to a URL plus its intrinsic width/height,
     * keyed under the given URL field name (`url` or `image_url` depending
     * on the block). Centralizes the null-safe shape every image-bearing
     * block above needs so each `resolveForPublic` arm stays a flat list of
     * fields instead of repeating the same three-key block.
     *
     * @return array<string, mixed>
     */
    private static function resolveImageFields(mixed $mediaId, string $urlKey): array
    {
        $image = Media::resolveImage($mediaId);

        return [
            $urlKey => $image['url'] ?? null,
            'width' => $image['width'] ?? null,
            'height' => $image['height'] ?? null,
        ];
    }
}
