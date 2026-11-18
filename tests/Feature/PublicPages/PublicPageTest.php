<?php

namespace Tests\Feature\PublicPages;

use App\Models\Media;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_published_page_returns_200_with_server_rendered_content(): void
    {
        $page = Page::factory()->published()->create([
            'title' => 'Tentang Prabu',
            'slug' => 'tentang-prabu',
            'content' => ['blocks' => [
                ['type' => 'rich_text', 'data' => ['title' => 'Sejarah', 'body' => 'Prabu adalah event organizer terkemuka.']],
            ]],
        ]);

        $response = $this->get('/tentang-prabu');

        $response->assertOk();
        $response->assertSee('Sejarah');
        $response->assertSee('Prabu adalah event organizer terkemuka.');
    }

    public function test_draft_page_cannot_be_publicly_accessed(): void
    {
        Page::factory()->create(['slug' => 'draft-page']); // default factory state is draft

        $this->get('/draft-page')->assertNotFound();
    }

    public function test_missing_slug_returns_404(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound();
    }

    public function test_page_seo_metadata_is_rendered(): void
    {
        $page = Page::factory()->published()->create([
            'title' => 'Fallback Title',
            'slug' => 'seo-test',
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'A custom description for search engines.',
        ]);

        $response = $this->get('/seo-test');

        $response->assertSee('<title>Custom SEO Title</title>', false);
        $response->assertSee('A custom description for search engines.', false);
    }

    public function test_meta_title_falls_back_to_page_title_when_unset(): void
    {
        Page::factory()->published()->create([
            'title' => 'The Page Title',
            'slug' => 'no-meta-title',
            'meta_title' => null,
        ]);

        $this->get('/no-meta-title')->assertSee('<title>The Page Title</title>', false);
    }

    public function test_navigation_appears_in_public_layout(): void
    {
        Page::factory()->published()->create(['slug' => 'with-nav']);
        NavigationItem::factory()->create(['label' => 'About Us', 'url' => '/about', 'visible' => true]);

        $response = $this->get('/with-nav');

        $response->assertSee('About Us');
    }

    public function test_only_visible_navigation_items_are_rendered(): void
    {
        Page::factory()->published()->create(['slug' => 'with-nav-2']);
        NavigationItem::factory()->create(['label' => 'Visible Item', 'visible' => true]);
        NavigationItem::factory()->hidden()->create(['label' => 'Hidden Item']);

        $response = $this->get('/with-nav-2');

        $response->assertSee('Visible Item');
        $response->assertDontSee('Hidden Item');
    }

    public function test_public_renderer_resolves_media_urls(): void
    {
        $media = Media::factory()->create();
        $page = Page::factory()->published()->create([
            'slug' => 'with-media',
            'content' => ['blocks' => [
                ['type' => 'image', 'data' => ['media_id' => $media->id, 'alt_text' => 'A photo']],
            ]],
        ]);

        $expectedUrl = Storage::disk($media->disk)->url($media->path);

        $response = $this->get('/with-media');

        $response->assertSee($expectedUrl, false);
        $response->assertSee('A photo', false);
    }

    public function test_image_bearing_blocks_render_width_height_and_lazy_loading_attributes(): void
    {
        $media = Media::factory()->create(['width' => 1200, 'height' => 800]);
        Page::factory()->published()->create([
            'slug' => 'image-dimensions',
            'content' => ['blocks' => [
                ['type' => 'hero', 'data' => ['title' => 'Hero', 'image_media_id' => $media->id]],
                ['type' => 'image', 'data' => ['media_id' => $media->id, 'alt_text' => 'Photo']],
            ]],
        ]);

        $response = $this->get('/image-dimensions');

        $response->assertOk();
        $response->assertSee('width="1200"', false);
        $response->assertSee('height="800"', false);
        // The image block (below the hero) is non-critical — lazy loaded.
        // The hero itself is not, since it's the likely LCP element.
        $response->assertSee('loading="lazy"', false);
    }

    public function test_hero_and_cta_blocks_render_their_content(): void
    {
        Page::factory()->published()->create([
            'slug' => 'full-page',
            'content' => ['blocks' => [
                ['type' => 'hero', 'data' => ['title' => 'Welcome to Prabu', 'subtitle' => 'Event organizer']],
                ['type' => 'cta', 'data' => ['title' => 'Ready?', 'button_label' => 'Contact Us', 'url' => '/contact']],
            ]],
        ]);

        $response = $this->get('/full-page');

        $response->assertSee('Welcome to Prabu');
        $response->assertSee('Event organizer');
        $response->assertSee('Contact Us');
        $response->assertSee('href="/contact"', false);
    }

    public function test_unknown_block_type_stored_directly_in_the_database_is_skipped_safely(): void
    {
        // Bypasses request validation entirely to simulate data that
        // somehow ended up unwhitelisted — the renderer must still not
        // choke on it or attempt to execute/load anything for it.
        $page = Page::factory()->published()->create([
            'slug' => 'weird-block',
            'content' => ['blocks' => [
                ['type' => 'not_a_real_block', 'data' => ['title' => 'Should not appear']],
                ['type' => 'rich_text', 'data' => ['body' => 'This should still render.']],
            ]],
        ]);

        $response = $this->get('/weird-block');

        $response->assertOk();
        $response->assertDontSee('Should not appear');
        $response->assertSee('This should still render.');
    }

    public function test_rich_text_content_cannot_inject_a_script_tag(): void
    {
        Page::factory()->published()->create([
            'slug' => 'xss-test',
            'content' => ['blocks' => [
                ['type' => 'rich_text', 'data' => ['body' => '<script>alert(1)</script>']],
            ]],
        ]);

        $response = $this->get('/xss-test');

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_schedule_block_renders_events_with_localized_date(): void
    {
        app()->setLocale('id');
        Page::factory()->published()->create([
            'slug' => 'schedule-blade',
            'content' => ['blocks' => [
                ['type' => 'schedule', 'data' => ['title' => 'Acara', 'items' => [
                    ['title' => 'Akad', 'date' => '2026-06-13', 'start_time' => '08:00', 'end_time' => '10:00', 'venue' => 'Masjid Raya', 'address' => 'Jl. Merdeka 1', 'maps_url' => 'https://maps.app.goo.gl/abc'],
                ]]],
            ]],
        ]);

        $response = $this->get('/schedule-blade');

        $response->assertOk();
        $response->assertSee('Akad');
        $response->assertSee('Sabtu, 13 Juni 2026');
        $response->assertSee('08:00 – 10:00');
        $response->assertSee('Masjid Raya');
        $response->assertSee('https://maps.app.goo.gl/abc', false);
    }

    public function test_couple_block_renders_names_parents_photo_and_instagram_link(): void
    {
        $media = Media::factory()->create();
        Page::factory()->published()->create([
            'slug' => 'couple-blade',
            'content' => ['blocks' => [
                ['type' => 'couple', 'data' => ['title' => 'Mempelai', 'items' => [
                    ['name' => 'Raka Pratama', 'parents' => 'Putra dari Bapak A & Ibu B', 'instagram' => 'raka', 'image_media_id' => $media->id],
                    ['name' => 'Sekar Ayu'],
                ]]],
            ]],
        ]);

        $response = $this->get('/couple-blade');

        $response->assertOk();
        $response->assertSeeInOrder(['Mempelai', 'Raka Pratama', 'Putra dari Bapak A &amp; Ibu B', 'Sekar Ayu'], false);
        $response->assertSee('href="https://instagram.com/raka"', false);
        $response->assertSee(Storage::disk($media->disk)->url($media->path), false);
    }

    public function test_menu_block_renders_names_prices_and_images(): void
    {
        $media = Media::factory()->create();
        Page::factory()->published()->create([
            'slug' => 'menu-blade',
            'content' => ['blocks' => [
                ['type' => 'menu', 'data' => ['title' => 'Menu Kami', 'items' => [
                    ['name' => 'Takoyaki Original', 'description' => 'Saus original', 'price' => '15000', 'image_media_id' => $media->id],
                ]]],
            ]],
        ]);

        $expectedUrl = Storage::disk($media->disk)->url($media->path);

        $response = $this->get('/menu-blade');

        $response->assertOk();
        $response->assertSee('Menu Kami');
        $response->assertSee('Takoyaki Original');
        $response->assertSee('15000');
        $response->assertSee($expectedUrl, false);
    }

    public function test_gallery_block_renders_all_images(): void
    {
        $first = Media::factory()->create();
        $second = Media::factory()->create();
        Page::factory()->published()->create([
            'slug' => 'gallery-blade',
            'content' => ['blocks' => [
                ['type' => 'gallery', 'data' => ['title' => 'Galeri', 'items' => [
                    ['media_id' => $first->id, 'alt_text' => 'Foto satu'],
                    ['media_id' => $second->id, 'alt_text' => 'Foto dua'],
                ]]],
            ]],
        ]);

        $response = $this->get('/gallery-blade');

        $response->assertOk();
        $response->assertSee('Galeri');
        $response->assertSee(Storage::disk($first->disk)->url($first->path), false);
        $response->assertSee(Storage::disk($second->disk)->url($second->path), false);
    }

    public function test_site_name_and_contact_from_settings_appear_in_the_layout(): void
    {
        $setting = Setting::current();
        $setting->site_identity = array_merge($setting->site_identity, ['name' => 'Prabu Events']);
        $setting->contact = ['email' => 'hello@prabu.test', 'phone' => null, 'address' => null];
        $setting->save();

        Page::factory()->published()->create(['slug' => 'settings-test']);

        $response = $this->get('/settings-test');

        $response->assertSee('Prabu Events');
        $response->assertSee('hello@prabu.test');
    }

    public function test_admin_spa_shell_routes_are_not_captured_by_the_public_page_route(): void
    {
        // These must still serve the Vue SPA shell, not 404 or attempt to
        // look up a Page with slug "login"/"dashboard".
        $this->get('/login')->assertOk()->assertViewIs('app');
        $this->get('/dashboard')->assertOk()->assertViewIs('app');
        $this->get('/admin/users')->assertOk()->assertViewIs('app');
    }
}
