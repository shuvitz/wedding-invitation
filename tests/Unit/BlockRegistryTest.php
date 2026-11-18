<?php

namespace Tests\Unit;

use App\Services\BlockRegistry;
use PHPUnit\Framework\TestCase;

class BlockRegistryTest extends TestCase
{
    public function test_types_lists_exactly_the_whitelisted_block_types(): void
    {
        $this->assertEqualsCanonicalizing(
            ['hero', 'rich_text', 'image', 'cta', 'menu', 'gallery', 'schedule', 'form', 'couple', 'gift', 'cover'],
            BlockRegistry::types()
        );
    }

    public function test_view_returns_null_for_an_unknown_type(): void
    {
        $this->assertNull(BlockRegistry::view('script'));
        $this->assertNull(BlockRegistry::view(''));
        $this->assertNull(BlockRegistry::view(null));
    }

    public function test_view_resolves_a_view_for_every_whitelisted_type_except_form(): void
    {
        foreach (array_diff(BlockRegistry::types(), ['form']) as $type) {
            $this->assertNotNull(BlockRegistry::view($type), "Expected a view for block type [{$type}]");
        }

        // Deliberate: forms are Nuxt-only (see BlockRegistry::view()).
        $this->assertNull(BlockRegistry::view('form'));
    }

    public function test_submission_rules_follow_field_types(): void
    {
        $rules = BlockRegistry::submissionRules([
            ['name' => 'name', 'type' => 'text', 'required' => true],
            ['name' => 'note', 'type' => 'textarea'],
            ['name' => 'guests', 'type' => 'number', 'required' => false],
            ['name' => 'choice', 'type' => 'select', 'options' => ['A', 'B']],
        ]);

        $this->assertSame(['name', 'note', 'guests', 'choice'], array_keys($rules));
        $this->assertSame(['required', 'string', 'max:255'], $rules['name']);
        $this->assertSame(['nullable', 'string', 'max:5000'], $rules['note']);
        $this->assertContains('numeric', $rules['guests']);
        $this->assertSame('in:"A","B"', (string) $rules['choice'][2]);
    }

    public function test_rules_for_returns_empty_for_an_unknown_type(): void
    {
        $this->assertSame([], BlockRegistry::rulesFor('not-a-real-type', 'content.blocks.0.data'));
    }

    public function test_rules_for_cta_rejects_unsafe_url_schemes_by_regex(): void
    {
        $rules = BlockRegistry::rulesFor('cta', 'content.blocks.0.data');

        $this->assertArrayHasKey('content.blocks.0.data.url', $rules);
        $urlRules = $rules['content.blocks.0.data.url'];

        $regexRule = collect($urlRules)->first(fn ($rule) => is_string($rule) && str_starts_with($rule, 'regex:'));
        $this->assertNotNull($regexRule, 'Expected a regex rule constraining the CTA url.');

        $pattern = substr($regexRule, strlen('regex:'));

        $this->assertSame(1, preg_match($pattern, '/contact'));
        $this->assertSame(1, preg_match($pattern, 'https://example.com'));
        $this->assertSame(0, preg_match($pattern, 'javascript:alert(1)'));
        $this->assertSame(0, preg_match($pattern, 'data:text/html,<script>alert(1)</script>'));
    }

    public function test_resolve_for_public_returns_null_for_an_unknown_type(): void
    {
        $this->assertNull(BlockRegistry::resolveForPublic(['type' => 'not_real', 'data' => []]));
    }

    public function test_resolve_for_public_never_includes_a_raw_media_id(): void
    {
        // No DB access in this plain TestCase — null media references
        // resolve without hitting Media::resolveUrl()'s query, which is
        // enough to prove the *_media_id keys never survive translation
        // (the DB-backed resolution path is covered by
        // Tests\Feature\PublicApi\PublicApiTest).
        $hero = BlockRegistry::resolveForPublic(['type' => 'hero', 'data' => ['title' => 'x', 'image_media_id' => null]]);
        $image = BlockRegistry::resolveForPublic(['type' => 'image', 'data' => ['media_id' => null, 'alt_text' => 'x']]);

        $this->assertArrayNotHasKey('image_media_id', $hero['data']);
        $this->assertArrayNotHasKey('media_id', $image['data']);
        $this->assertArrayHasKey('image_url', $hero['data']);
        $this->assertArrayHasKey('url', $image['data']);
    }

    public function test_rules_for_menu_validates_repeating_items(): void
    {
        $rules = BlockRegistry::rulesFor('menu', 'content.blocks.0.data');

        $this->assertArrayHasKey('content.blocks.0.data.items.*.name', $rules);
        $this->assertArrayHasKey('content.blocks.0.data.items.*.price', $rules);
        $this->assertArrayHasKey('content.blocks.0.data.items.*.description', $rules);
        $this->assertArrayHasKey('content.blocks.0.data.items.*.image_media_id', $rules);
    }

    public function test_rules_for_schedule_validates_repeating_items(): void
    {
        $rules = BlockRegistry::rulesFor('schedule', 'content.blocks.0.data');

        foreach (['title', 'date', 'start_time', 'end_time', 'venue', 'address', 'maps_url'] as $field) {
            $this->assertArrayHasKey("content.blocks.0.data.items.*.{$field}", $rules);
        }
    }

    public function test_resolve_for_public_passes_schedule_items_through(): void
    {
        $item = [
            'title' => 'Akad',
            'date' => '2026-06-14',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'venue' => 'Masjid Raya',
            'address' => 'Jl. Merdeka 1',
            'maps_url' => 'https://maps.app.goo.gl/abc',
        ];

        $schedule = BlockRegistry::resolveForPublic(['type' => 'schedule', 'data' => ['title' => 'Acara', 'items' => [$item]]]);
        $empty = BlockRegistry::resolveForPublic(['type' => 'schedule', 'data' => []]);

        $this->assertSame($item, $schedule['data']['items'][0]);
        $this->assertSame([], $empty['data']['items']);
    }

    public function test_rules_for_gallery_validates_repeating_items(): void
    {
        $rules = BlockRegistry::rulesFor('gallery', 'content.blocks.0.data');

        $this->assertArrayHasKey('content.blocks.0.data.items.*.media_id', $rules);
        $this->assertArrayHasKey('content.blocks.0.data.items.*.alt_text', $rules);
    }

    public function test_resolve_for_public_never_includes_raw_media_ids_for_menu_or_gallery(): void
    {
        $menu = BlockRegistry::resolveForPublic(['type' => 'menu', 'data' => [
            'title' => 'Menu',
            'items' => [['name' => 'Takoyaki', 'price' => '15000', 'image_media_id' => null]],
        ]]);
        $gallery = BlockRegistry::resolveForPublic(['type' => 'gallery', 'data' => [
            'title' => 'Gallery',
            'items' => [['media_id' => null, 'alt_text' => 'x']],
        ]]);

        $this->assertArrayNotHasKey('image_media_id', $menu['data']['items'][0]);
        $this->assertArrayHasKey('image_url', $menu['data']['items'][0]);
        $this->assertArrayNotHasKey('media_id', $gallery['data']['items'][0]);
        $this->assertArrayHasKey('url', $gallery['data']['items'][0]);
    }

    public function test_resolve_for_public_handles_empty_menu_and_gallery_items(): void
    {
        $menu = BlockRegistry::resolveForPublic(['type' => 'menu', 'data' => ['title' => 'Menu']]);
        $gallery = BlockRegistry::resolveForPublic(['type' => 'gallery', 'data' => ['title' => 'Gallery']]);

        $this->assertSame([], $menu['data']['items']);
        $this->assertSame([], $gallery['data']['items']);
    }
}
