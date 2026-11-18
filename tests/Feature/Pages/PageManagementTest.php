<?php

namespace Tests\Feature\Pages;

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ── List ───────────────────────────────────────────

    public function test_admin_can_list_pages(): void
    {
        $admin = User::factory()->admin()->create();
        Page::factory()->count(3)->create();

        $this->actingAs($admin)->getJson('/api/pages')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_editor_can_list_pages(): void
    {
        $editor = User::factory()->create();
        Page::factory()->create();

        $this->actingAs($editor)->getJson('/api/pages')->assertOk();
    }

    public function test_guest_cannot_list_pages(): void
    {
        $this->getJson('/api/pages')->assertStatus(401);
    }

    public function test_admin_can_filter_pages_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Page::factory()->create();
        Page::factory()->published()->create();

        $response = $this->actingAs($admin)->getJson('/api/pages?status=published');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    // ── Create ─────────────────────────────────────────

    public function test_admin_can_create_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Tentang Prabu',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Tentang Prabu')
            ->assertJsonPath('data.slug', 'tentang-prabu')
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_editor_can_create_a_draft_page(): void
    {
        $editor = User::factory()->create();

        // Even if a status is smuggled into the payload, StorePageRequest
        // doesn't accept the field at all — creation is always draft.
        $response = $this->actingAs($editor)->postJson('/api/pages', [
            'title' => 'Editor Page',
            'status' => 'published',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'draft');
        $this->assertSame('draft', Page::first()->status->value);
    }

    public function test_slug_defaults_from_title_when_omitted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/pages', ['title' => 'Tentang Prabu!'])
            ->assertCreated()->assertJsonPath('data.slug', 'tentang-prabu');
    }

    public function test_slug_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Page::factory()->create(['slug' => 'tentang-prabu']);

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Tentang Prabu',
            'slug' => 'tentang-prabu',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['slug']);
    }

    public function test_create_requires_title(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/pages', [])
            ->assertStatus(422)->assertJsonValidationErrors(['title']);
    }

    public function test_block_whitelist_rejects_unknown_block_types(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Page',
            'content' => ['blocks' => [
                ['type' => 'testimonial_carousel', 'data' => ['title' => 'x']],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['content.blocks.0.type']);
        $this->assertSame(0, Page::count());
    }

    public function test_media_references_in_blocks_are_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Page With Bad Media',
            'content' => ['blocks' => [
                ['type' => 'image', 'data' => ['media_id' => 999999]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['content.blocks.0.data.media_id']);
    }

    public function test_valid_blocks_with_real_media_are_accepted(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Tentang Prabu',
            'content' => ['blocks' => [
                ['type' => 'hero', 'data' => ['title' => 'Welcome', 'image_media_id' => $media->id]],
                ['type' => 'rich_text', 'data' => ['body' => 'Some body text.']],
                ['type' => 'image', 'data' => ['media_id' => $media->id, 'alt_text' => 'A photo']],
                ['type' => 'cta', 'data' => ['title' => 'Join us', 'button_label' => 'Register', 'url' => '/register']],
            ]],
        ]);

        $response->assertCreated();
        $this->assertCount(4, Page::first()->content['blocks']);
    }

    public function test_cta_block_rejects_a_javascript_url_scheme(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Malicious CTA',
            'content' => ['blocks' => [
                ['type' => 'cta', 'data' => ['title' => 'x', 'button_label' => 'Click', 'url' => 'javascript:alert(1)']],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['content.blocks.0.data.url']);
    }

    public function test_menu_block_accepts_valid_items(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Menu Page',
            'content' => ['blocks' => [
                ['type' => 'menu', 'data' => ['title' => 'Menu', 'items' => [
                    ['name' => 'Takoyaki Original', 'description' => 'Saus original', 'price' => '15000', 'image_media_id' => $media->id],
                    ['name' => 'Takoyaki Cheese', 'price' => '18000'],
                ]]],
            ]],
        ]);

        $response->assertCreated();
        $this->assertCount(2, Page::first()->content['blocks'][0]['data']['items']);
    }

    public function test_menu_item_requires_name_and_price(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Menu',
            'content' => ['blocks' => [
                ['type' => 'menu', 'data' => ['items' => [['description' => 'no name or price']]]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'content.blocks.0.data.items.0.name',
            'content.blocks.0.data.items.0.price',
        ]);
    }

    public function test_menu_item_validates_media_reference(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Menu Media',
            'content' => ['blocks' => [
                ['type' => 'menu', 'data' => ['items' => [
                    ['name' => 'Takoyaki', 'price' => '15000', 'image_media_id' => 999999],
                ]]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['content.blocks.0.data.items.0.image_media_id']);
    }

    public function test_schedule_block_accepts_valid_items(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Schedule Page',
            'content' => ['blocks' => [
                ['type' => 'schedule', 'data' => ['title' => 'Acara', 'items' => [
                    ['title' => 'Akad', 'date' => '2026-06-14', 'start_time' => '08:00', 'end_time' => '10:00', 'venue' => 'Masjid Raya', 'address' => 'Jl. Merdeka 1', 'maps_url' => 'https://maps.app.goo.gl/abc'],
                    ['title' => 'Resepsi', 'date' => '2026-06-14'],
                ]]],
            ]],
        ]);

        $response->assertCreated();
        $this->assertCount(2, Page::first()->content['blocks'][0]['data']['items']);
    }

    public function test_schedule_item_requires_title_and_valid_date_and_times(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Schedule',
            'content' => ['blocks' => [
                ['type' => 'schedule', 'data' => ['items' => [
                    ['venue' => 'no title or date'],
                    ['title' => 'x', 'date' => '2026-02-30', 'start_time' => '25:00', 'end_time' => '8am'],
                ]]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'content.blocks.0.data.items.0.title',
            'content.blocks.0.data.items.0.date',
            'content.blocks.0.data.items.1.date',
            'content.blocks.0.data.items.1.start_time',
            'content.blocks.0.data.items.1.end_time',
        ]);
    }

    public function test_schedule_item_rejects_non_http_maps_url(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Maps',
            'content' => ['blocks' => [
                ['type' => 'schedule', 'data' => ['items' => [
                    ['title' => 'Akad', 'date' => '2026-06-14', 'maps_url' => 'javascript:alert(1)'],
                ]]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['content.blocks.0.data.items.0.maps_url']);
    }

    public function test_couple_block_accepts_two_people(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Couple Page',
            'content' => ['blocks' => [
                ['type' => 'couple', 'data' => ['title' => 'Mempelai', 'items' => [
                    ['name' => 'Raka', 'parents' => 'Putra dari Bapak A & Ibu B', 'instagram' => 'raka.pratama_', 'image_media_id' => $media->id],
                    ['name' => 'Sekar'],
                ]]],
            ]],
        ]);

        $response->assertCreated();
        $this->assertCount(2, Page::first()->content['blocks'][0]['data']['items']);
    }

    public function test_couple_block_requires_exactly_two_named_people_and_a_bare_instagram_username(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Couple',
            'content' => ['blocks' => [
                ['type' => 'couple', 'data' => ['items' => [['name' => 'Raka']]]],
                ['type' => 'couple', 'data' => ['items' => [
                    ['parents' => 'no name'],
                    ['name' => 'Sekar', 'instagram' => 'https://instagram.com/x'],
                ]]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'content.blocks.0.data.items',
            'content.blocks.1.data.items.0.name',
            'content.blocks.1.data.items.1.instagram',
        ]);
    }

    public function test_gift_block_accepts_valid_accounts(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Gift Page',
            'content' => ['blocks' => [
                ['type' => 'gift', 'data' => ['title' => 'Amplop Digital', 'description' => 'Doa restu Anda sudah cukup.', 'items' => [
                    ['bank' => 'BCA', 'account_number' => '1234 5678 90', 'account_name' => 'Raka Pratama'],
                    ['bank' => 'GoPay', 'account_number' => '0812-3456-7890', 'account_name' => 'Sekar Ayu'],
                ]]],
            ]],
        ]);

        $response->assertCreated();
        $this->assertCount(2, Page::first()->content['blocks'][0]['data']['items']);
    }

    public function test_gift_block_requires_accounts_with_numeric_account_numbers(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Gift',
            'content' => ['blocks' => [
                ['type' => 'gift', 'data' => ['items' => []]],
                ['type' => 'gift', 'data' => ['items' => [
                    ['account_number' => '123'],
                    ['bank' => 'BCA', 'account_number' => '<b>123</b>', 'account_name' => 'Raka'],
                ]]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'content.blocks.0.data.items',
            'content.blocks.1.data.items.0.bank',
            'content.blocks.1.data.items.0.account_name',
            'content.blocks.1.data.items.1.account_number',
        ]);
    }

    public function test_cover_block_requires_names(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Cover Page',
            'content' => ['blocks' => [['type' => 'cover', 'data' => ['names' => 'Raka & Sekar', 'greeting' => 'Kepada Yth.']]]],
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Cover',
            'content' => ['blocks' => [['type' => 'cover', 'data' => ['title' => 'The Wedding of']]]],
        ])->assertStatus(422)->assertJsonValidationErrors(['content.blocks.0.data.names']);
    }

    public function test_page_rejects_more_than_one_cover_block(): void
    {
        $admin = User::factory()->admin()->create();
        $cover = ['type' => 'cover', 'data' => ['names' => 'Raka & Sekar']];

        $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Two Covers',
            'content' => ['blocks' => [$cover, $cover]],
        ])->assertStatus(422)->assertJsonValidationErrors(['content.blocks']);
    }

    public function test_gallery_block_accepts_valid_items(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Gallery Page',
            'content' => ['blocks' => [
                ['type' => 'gallery', 'data' => ['title' => 'Gallery', 'items' => [
                    ['media_id' => $media->id, 'alt_text' => 'A photo'],
                ]]],
            ]],
        ]);

        $response->assertCreated();
        $this->assertCount(1, Page::first()->content['blocks'][0]['data']['items']);
    }

    public function test_gallery_item_requires_media_id(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Gallery',
            'content' => ['blocks' => [
                ['type' => 'gallery', 'data' => ['items' => [['alt_text' => 'missing media id']]]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['content.blocks.0.data.items.0.media_id']);
    }

    public function test_gallery_item_validates_media_reference(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Gallery Media',
            'content' => ['blocks' => [
                ['type' => 'gallery', 'data' => ['items' => [['media_id' => 999999]]]],
            ]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['content.blocks.0.data.items.0.media_id']);
    }

    public function test_editor_cannot_create_an_already_published_page_via_status_field(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->postJson('/api/pages', [
            'title' => 'Sneaky',
            'status' => 'published',
        ])->assertCreated();

        $this->assertSame('draft', Page::first()->status->value);
    }

    // ── Update ─────────────────────────────────────────

    public function test_admin_can_update_a_page(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create(['title' => 'Old']);

        $response = $this->actingAs($admin)->putJson("/api/pages/{$page->id}", ['title' => 'New']);

        $response->assertOk()->assertJsonPath('data.title', 'New');
    }

    public function test_editor_can_update_their_own_draft_page(): void
    {
        $editor = User::factory()->create();
        $page = Page::factory()->create(['title' => 'Old']);

        $this->actingAs($editor)->putJson("/api/pages/{$page->id}", ['title' => 'New'])
            ->assertOk()->assertJsonPath('data.title', 'New');
    }

    public function test_editor_cannot_update_a_published_page(): void
    {
        $editor = User::factory()->create();
        $page = Page::factory()->published()->create();

        $this->actingAs($editor)->putJson("/api/pages/{$page->id}", ['title' => 'Hijacked'])
            ->assertStatus(403);
    }

    public function test_update_does_not_accept_a_status_field(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create();

        // Even for an admin, the general update endpoint must not be able
        // to change status — that's exclusively the /status endpoint.
        $this->actingAs($admin)->putJson("/api/pages/{$page->id}", [
            'title' => 'Still a draft',
            'status' => 'published',
        ])->assertOk();

        $this->assertSame('draft', $page->fresh()->status->value);
    }

    public function test_slug_is_not_changed_on_update_unless_explicitly_provided(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create(['title' => 'Original', 'slug' => 'original']);

        $this->actingAs($admin)->putJson("/api/pages/{$page->id}", ['title' => 'Completely Different Title'])
            ->assertOk();

        $this->assertSame('original', $page->fresh()->slug);
    }

    // ── Publish ────────────────────────────────────────

    public function test_admin_can_publish_a_page(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create();

        $response = $this->actingAs($admin)->patchJson("/api/pages/{$page->id}/status", ['status' => 'published']);

        $response->assertOk()->assertJsonPath('data.status', 'published');
        $this->assertNotNull($page->fresh()->published_at);
    }

    public function test_editor_cannot_publish_a_page(): void
    {
        $editor = User::factory()->create();
        $page = Page::factory()->create();

        $this->actingAs($editor)->patchJson("/api/pages/{$page->id}/status", ['status' => 'published'])
            ->assertStatus(403);

        $this->assertSame('draft', $page->fresh()->status->value);
    }

    public function test_editor_cannot_unpublish_a_page_either(): void
    {
        $editor = User::factory()->create();
        $page = Page::factory()->published()->create();

        $this->actingAs($editor)->patchJson("/api/pages/{$page->id}/status", ['status' => 'draft'])
            ->assertStatus(403);
    }

    // ── Homepage ───────────────────────────────────────

    public function test_admin_can_set_a_published_page_as_home(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->published()->create();

        $response = $this->actingAs($admin)->patchJson("/api/pages/{$page->id}/home", ['is_home' => true]);

        $response->assertOk()->assertJsonPath('data.is_home', true);
        $this->assertTrue($page->fresh()->is_home);
    }

    public function test_admin_cannot_set_a_draft_page_as_home(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create();

        $response = $this->actingAs($admin)->patchJson("/api/pages/{$page->id}/home", ['is_home' => true]);

        $response->assertStatus(422)->assertJsonValidationErrors(['is_home']);
        $this->assertFalse($page->fresh()->is_home);
    }

    public function test_setting_a_new_home_page_clears_the_previous_one(): void
    {
        $admin = User::factory()->admin()->create();
        $oldHome = Page::factory()->published()->create(['is_home' => true]);
        $newHome = Page::factory()->published()->create();

        $this->actingAs($admin)->patchJson("/api/pages/{$newHome->id}/home", ['is_home' => true])->assertOk();

        $this->assertFalse($oldHome->fresh()->is_home);
        $this->assertTrue($newHome->fresh()->is_home);
    }

    public function test_unpublishing_the_home_page_clears_its_home_designation(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->published()->create(['is_home' => true]);

        $this->actingAs($admin)->patchJson("/api/pages/{$page->id}/status", ['status' => 'draft'])->assertOk();

        $this->assertFalse($page->fresh()->is_home);
    }

    public function test_editor_cannot_set_a_page_as_home(): void
    {
        $editor = User::factory()->create();
        $page = Page::factory()->published()->create();

        $this->actingAs($editor)->patchJson("/api/pages/{$page->id}/home", ['is_home' => true])
            ->assertStatus(403);

        $this->assertFalse($page->fresh()->is_home);
    }

    // ── Delete ─────────────────────────────────────────

    public function test_admin_can_delete_a_page(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/pages/{$page->id}")->assertOk();

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_editor_cannot_delete_a_page(): void
    {
        $editor = User::factory()->create();
        $page = Page::factory()->create();

        $this->actingAs($editor)->deleteJson("/api/pages/{$page->id}")->assertStatus(403);

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }
}
