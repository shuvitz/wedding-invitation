<?php

namespace Tests\Feature\PublicApi;

use App\Models\Media;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ── Settings ───────────────────────────────────────

    public function test_public_settings_endpoint_requires_no_authentication(): void
    {
        $setting = Setting::current();
        $setting->site_identity = array_merge($setting->site_identity, ['name' => 'Prabu Events']);
        $setting->save();

        $response = $this->getJson('/api/public/settings');

        $response->assertOk()->assertJsonPath('data.site_identity.name', 'Prabu Events');
    }

    public function test_public_settings_response_contains_no_user_information(): void
    {
        $response = $this->getJson('/api/public/settings');

        $response->assertOk()
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.created_by');
    }

    // ── Navigation ─────────────────────────────────────

    public function test_public_navigation_only_returns_visible_items_in_order(): void
    {
        NavigationItem::factory()->create(['label' => 'Second', 'order' => 2, 'visible' => true]);
        NavigationItem::factory()->create(['label' => 'First', 'order' => 1, 'visible' => true]);
        NavigationItem::factory()->hidden()->create(['label' => 'Hidden']);

        $response = $this->getJson('/api/public/navigation');

        $response->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.label', 'First')
            ->assertJsonPath('data.1.label', 'Second');
    }

    public function test_public_navigation_items_do_not_expose_id_or_visible(): void
    {
        NavigationItem::factory()->create();

        $response = $this->getJson('/api/public/navigation');

        $response->assertOk()
            ->assertJsonMissingPath('data.0.id')
            ->assertJsonMissingPath('data.0.visible');
    }

    // ── Pages ──────────────────────────────────────────

    public function test_public_page_endpoint_returns_published_page(): void
    {
        Page::factory()->published()->create([
            'title' => 'Tentang Prabu',
            'slug' => 'tentang-prabu',
            'meta_description' => 'Prabu event organizer',
        ]);

        $response = $this->getJson('/api/public/pages/tentang-prabu');

        $response->assertOk()
            ->assertJsonPath('data.title', 'Tentang Prabu')
            ->assertJsonPath('data.meta_description', 'Prabu event organizer');
    }

    public function test_public_page_endpoint_404s_for_a_draft(): void
    {
        Page::factory()->create(['slug' => 'draft-page']);

        $this->getJson('/api/public/pages/draft-page')->assertStatus(404);
    }

    public function test_public_page_endpoint_404s_for_a_missing_slug(): void
    {
        $this->getJson('/api/public/pages/does-not-exist')->assertStatus(404);
    }

    public function test_public_page_response_excludes_admin_only_fields(): void
    {
        $admin = User::factory()->admin()->create();
        Page::factory()->published()->create(['slug' => 'no-admin-fields', 'created_by' => $admin->id, 'updated_by' => $admin->id]);

        $response = $this->getJson('/api/public/pages/no-admin-fields');

        $response->assertOk()
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.status')
            ->assertJsonMissingPath('data.created_by')
            ->assertJsonMissingPath('data.updated_by');
    }

    public function test_public_page_resolves_media_urls_in_blocks(): void
    {
        $media = Media::factory()->create();
        Page::factory()->published()->create([
            'slug' => 'with-media',
            'content' => ['blocks' => [
                ['type' => 'image', 'data' => ['media_id' => $media->id, 'alt_text' => 'A photo']],
            ]],
        ]);

        $expectedUrl = Storage::disk($media->disk)->url($media->path);

        $response = $this->getJson('/api/public/pages/with-media');

        $response->assertOk()
            ->assertJsonPath('data.content.blocks.0.data.url', $expectedUrl)
            ->assertJsonMissingPath('data.content.blocks.0.data.media_id');
    }

    public function test_public_page_exposes_image_intrinsic_dimensions_for_hero_and_image_blocks(): void
    {
        $media = Media::factory()->create(['width' => 1600, 'height' => 900]);
        Page::factory()->published()->create([
            'slug' => 'with-dimensions',
            'content' => ['blocks' => [
                ['type' => 'hero', 'data' => ['title' => 'Hero', 'image_media_id' => $media->id]],
                ['type' => 'image', 'data' => ['media_id' => $media->id]],
            ]],
        ]);

        $response = $this->getJson('/api/public/pages/with-dimensions');

        $response->assertOk()
            ->assertJsonPath('data.content.blocks.0.data.width', 1600)
            ->assertJsonPath('data.content.blocks.0.data.height', 900)
            ->assertJsonPath('data.content.blocks.1.data.width', 1600)
            ->assertJsonPath('data.content.blocks.1.data.height', 900);
    }

    public function test_public_page_exposes_null_dimensions_when_a_block_has_no_image(): void
    {
        Page::factory()->published()->create([
            'slug' => 'no-image-hero',
            'content' => ['blocks' => [
                ['type' => 'hero', 'data' => ['title' => 'Hero without an image']],
            ]],
        ]);

        $response = $this->getJson('/api/public/pages/no-image-hero');

        $response->assertOk()
            ->assertJsonPath('data.content.blocks.0.data.width', null)
            ->assertJsonPath('data.content.blocks.0.data.height', null);
    }

    public function test_public_page_drops_unknown_block_types(): void
    {
        Page::factory()->published()->create([
            'slug' => 'weird-blocks',
            'content' => ['blocks' => [
                ['type' => 'not_a_real_type', 'data' => ['x' => 'y']],
                ['type' => 'rich_text', 'data' => ['body' => 'Real content.']],
            ]],
        ]);

        $response = $this->getJson('/api/public/pages/weird-blocks');

        $response->assertOk()->assertJsonCount(1, 'data.content.blocks')
            ->assertJsonPath('data.content.blocks.0.type', 'rich_text');
    }

    public function test_public_page_returns_schedule_items_with_raw_date_and_times(): void
    {
        Page::factory()->published()->create([
            'slug' => 'schedule-page',
            'content' => ['blocks' => [
                ['type' => 'schedule', 'data' => ['title' => 'Acara', 'items' => [
                    ['title' => 'Akad', 'date' => '2026-06-13', 'start_time' => '08:00', 'maps_url' => 'https://maps.app.goo.gl/abc'],
                ]]],
            ]],
        ]);

        $this->getJson('/api/public/pages/schedule-page')
            ->assertOk()
            ->assertJsonPath('data.content.blocks.0.type', 'schedule')
            ->assertJsonPath('data.content.blocks.0.data.items.0.title', 'Akad')
            ->assertJsonPath('data.content.blocks.0.data.items.0.date', '2026-06-13')
            ->assertJsonPath('data.content.blocks.0.data.items.0.start_time', '08:00')
            ->assertJsonPath('data.content.blocks.0.data.show_countdown', false)
            ->assertJsonPath('data.content.blocks.0.data.items.0.end_time', null)
            ->assertJsonPath('data.content.blocks.0.data.items.0.maps_url', 'https://maps.app.goo.gl/abc');
    }

    public function test_public_page_resolves_couple_photos_and_excludes_media_id(): void
    {
        $media = Media::factory()->create();
        Page::factory()->published()->create([
            'slug' => 'couple-page',
            'content' => ['blocks' => [
                ['type' => 'couple', 'data' => ['title' => 'Mempelai', 'items' => [
                    ['name' => 'Raka', 'parents' => 'Putra dari Bapak A', 'instagram' => 'raka', 'image_media_id' => $media->id],
                    ['name' => 'Sekar'],
                ]]],
            ]],
        ]);

        $this->getJson('/api/public/pages/couple-page')
            ->assertOk()
            ->assertJsonPath('data.content.blocks.0.type', 'couple')
            ->assertJsonPath('data.content.blocks.0.data.items.0.name', 'Raka')
            ->assertJsonPath('data.content.blocks.0.data.items.0.instagram', 'raka')
            ->assertJsonPath('data.content.blocks.0.data.items.0.image_url', Storage::disk($media->disk)->url($media->path))
            ->assertJsonPath('data.content.blocks.0.data.items.1.image_url', null)
            ->assertJsonMissingPath('data.content.blocks.0.data.items.0.image_media_id');
    }

    public function test_public_page_resolves_cover_image_and_excludes_media_id(): void
    {
        $media = Media::factory()->create();
        Page::factory()->published()->create([
            'slug' => 'cover-page',
            'content' => ['blocks' => [
                ['type' => 'cover', 'data' => ['title' => 'The Wedding of', 'names' => 'Raka & Sekar', 'image_media_id' => $media->id]],
            ]],
        ]);

        $this->getJson('/api/public/pages/cover-page')
            ->assertOk()
            ->assertJsonPath('data.content.blocks.0.type', 'cover')
            ->assertJsonPath('data.content.blocks.0.data.names', 'Raka & Sekar')
            ->assertJsonPath('data.content.blocks.0.data.greeting', null)
            ->assertJsonPath('data.content.blocks.0.data.image_url', Storage::disk($media->disk)->url($media->path))
            ->assertJsonMissingPath('data.content.blocks.0.data.image_media_id');
    }

    public function test_public_page_returns_gift_accounts(): void
    {
        $account = ['bank' => 'BCA', 'account_number' => '1234 5678 90', 'account_name' => 'Raka Pratama'];
        Page::factory()->published()->create([
            'slug' => 'gift-page',
            'content' => ['blocks' => [
                ['type' => 'gift', 'data' => ['title' => 'Amplop Digital', 'items' => [$account + ['extra' => 'dropped']]]],
            ]],
        ]);

        $this->getJson('/api/public/pages/gift-page')
            ->assertOk()
            ->assertJsonPath('data.content.blocks.0.type', 'gift')
            ->assertJsonPath('data.content.blocks.0.data.description', null)
            ->assertJsonPath('data.content.blocks.0.data.items.0', $account);
    }

    public function test_public_page_resolves_menu_item_media_and_excludes_media_id(): void
    {
        $media = Media::factory()->create();
        Page::factory()->published()->create([
            'slug' => 'menu-page',
            'content' => ['blocks' => [
                ['type' => 'menu', 'data' => ['title' => 'Menu', 'items' => [
                    ['name' => 'Takoyaki Original', 'description' => 'Saus original', 'price' => '15000', 'image_media_id' => $media->id],
                ]]],
            ]],
        ]);

        $expectedUrl = Storage::disk($media->disk)->url($media->path);

        $response = $this->getJson('/api/public/pages/menu-page');

        $response->assertOk()
            ->assertJsonPath('data.content.blocks.0.data.items.0.name', 'Takoyaki Original')
            ->assertJsonPath('data.content.blocks.0.data.items.0.price', '15000')
            ->assertJsonPath('data.content.blocks.0.data.items.0.image_url', $expectedUrl)
            ->assertJsonPath('data.content.blocks.0.data.items.0.width', $media->width)
            ->assertJsonPath('data.content.blocks.0.data.items.0.height', $media->height)
            ->assertJsonMissingPath('data.content.blocks.0.data.items.0.image_media_id');
    }

    public function test_public_page_resolves_gallery_item_media_and_excludes_media_id(): void
    {
        $media = Media::factory()->create();
        Page::factory()->published()->create([
            'slug' => 'gallery-page',
            'content' => ['blocks' => [
                ['type' => 'gallery', 'data' => ['title' => 'Gallery', 'items' => [
                    ['media_id' => $media->id, 'alt_text' => 'A photo'],
                ]]],
            ]],
        ]);

        $expectedUrl = Storage::disk($media->disk)->url($media->path);

        $response = $this->getJson('/api/public/pages/gallery-page');

        $response->assertOk()
            ->assertJsonPath('data.content.blocks.0.data.items.0.url', $expectedUrl)
            ->assertJsonPath('data.content.blocks.0.data.items.0.alt_text', 'A photo')
            ->assertJsonPath('data.content.blocks.0.data.items.0.width', $media->width)
            ->assertJsonPath('data.content.blocks.0.data.items.0.height', $media->height)
            ->assertJsonMissingPath('data.content.blocks.0.data.items.0.media_id');
    }

    // ── Homepage ───────────────────────────────────────

    public function test_public_home_endpoint_returns_the_designated_homepage(): void
    {
        Page::factory()->published()->create(['title' => 'Not Home']);
        Page::factory()->published()->create(['title' => 'The Homepage', 'is_home' => true]);

        $response = $this->getJson('/api/public/home');

        $response->assertOk()->assertJsonPath('data.title', 'The Homepage');
    }

    public function test_public_home_endpoint_404s_when_no_homepage_is_designated(): void
    {
        Page::factory()->published()->create();

        $this->getJson('/api/public/home')->assertStatus(404);
    }
}
