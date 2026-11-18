<?php

namespace Tests\Feature\Media;

use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ── List ───────────────────────────────────────────

    public function test_admin_can_list_media(): void
    {
        $admin = User::factory()->admin()->create();
        Media::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/media');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_editor_can_list_media(): void
    {
        $editor = User::factory()->create();
        Media::factory()->create();

        $this->actingAs($editor)->getJson('/api/media')->assertOk();
    }

    public function test_guest_cannot_access_media(): void
    {
        $this->getJson('/api/media')->assertStatus(401);
    }

    public function test_admin_can_search_media_by_original_name(): void
    {
        $admin = User::factory()->admin()->create();
        Media::factory()->create(['original_name' => 'sunset-beach.jpg']);
        Media::factory()->create(['original_name' => 'team-photo.jpg']);

        $response = $this->actingAs($admin)->getJson('/api/media?search=sunset');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.original_name', 'sunset-beach.jpg');
    }

    // ── Upload ─────────────────────────────────────────

    public function test_admin_can_upload_valid_image(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($admin)->postJson('/api/media', [
            'file' => $file,
            'alt_text' => 'A nice photo',
        ]);

        $response->assertCreated()->assertJsonPath('data.alt_text', 'A nice photo');

        $media = Media::first();
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_upload_metadata_is_stored_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->image('My Original Photo.jpg', 800, 600);

        $this->actingAs($admin)->postJson('/api/media', ['file' => $file])->assertCreated();

        $media = Media::first();

        $this->assertSame('My Original Photo.jpg', $media->original_name);
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertGreaterThan(0, $media->size);
        $this->assertSame($admin->id, $media->uploaded_by);
        // storage filename must not be derived from the original name
        $this->assertStringNotContainsString('My Original Photo', $media->path);
    }

    public function test_width_and_height_are_detected_for_uploaded_images(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($admin)->postJson('/api/media', ['file' => $file])->assertCreated();

        $media = Media::first();

        $this->assertSame(800, $media->width);
        $this->assertSame(600, $media->height);
    }

    public function test_public_url_is_generated_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($admin)->postJson('/api/media', ['file' => $file]);

        $media = Media::first();
        $expectedUrl = Storage::disk('public')->url($media->path);

        $response->assertJsonPath('data.url', $expectedUrl);
    }

    public function test_invalid_mime_type_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin)->postJson('/api/media', ['file' => $file]);

        $response->assertStatus(422)->assertJsonValidationErrors(['file']);
        $this->assertSame(0, Media::count());
    }

    public function test_oversized_file_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->image('big.jpg')->size(6000); // 6000 KB > 5MB limit

        $response = $this->actingAs($admin)->postJson('/api/media', ['file' => $file]);

        $response->assertStatus(422)->assertJsonValidationErrors(['file']);
        $this->assertSame(0, Media::count());
    }

    public function test_oversized_dimensions_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        // Thin but very wide — exceeds max_width without GD needing to
        // render a huge pixel buffer (a genuinely 9000x9000 image would
        // exhaust PHP's CLI memory limit just generating the fake).
        $file = UploadedFile::fake()->image('huge.jpg', 8001, 10);

        $response = $this->actingAs($admin)->postJson('/api/media', ['file' => $file]);

        $response->assertStatus(422)->assertJsonValidationErrors(['file']);
    }

    public function test_upload_requires_a_file(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/media', [])
            ->assertStatus(422)->assertJsonValidationErrors(['file']);
    }

    public function test_editor_cannot_upload_media(): void
    {
        $editor = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->actingAs($editor)->postJson('/api/media', ['file' => $file])->assertStatus(403);
        $this->assertSame(0, Media::count());
    }

    // ── Update ─────────────────────────────────────────

    public function test_admin_can_update_alt_text(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create(['alt_text' => null]);

        $response = $this->actingAs($admin)->patchJson("/api/media/{$media->id}", [
            'alt_text' => 'Updated description',
        ]);

        $response->assertOk()->assertJsonPath('data.alt_text', 'Updated description');
    }

    public function test_editor_cannot_update_media(): void
    {
        $editor = User::factory()->create();
        $media = Media::factory()->create();

        $this->actingAs($editor)->patchJson("/api/media/{$media->id}", [
            'alt_text' => 'Hijacked',
        ])->assertStatus(403);
    }

    // ── Delete ─────────────────────────────────────────

    public function test_admin_can_delete_unreferenced_media(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_physical_file_is_removed_when_media_is_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $file = UploadedFile::fake()->image('photo.jpg');
        $this->actingAs($admin)->postJson('/api/media', ['file' => $file])->assertCreated();

        $media = Media::first();
        Storage::disk('public')->assertExists($media->path);

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")->assertOk();

        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_media_referenced_as_logo_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();

        $setting = Setting::current();
        $setting->site_identity = array_merge($setting->site_identity, ['logo_media_id' => $media->id]);
        $setting->save();

        $response = $this->actingAs($admin)->deleteJson("/api/media/{$media->id}");

        $response->assertStatus(422)->assertJsonValidationErrors(['media']);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_media_referenced_as_favicon_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();

        $setting = Setting::current();
        $setting->site_identity = array_merge($setting->site_identity, ['favicon_media_id' => $media->id]);
        $setting->save();

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")
            ->assertStatus(422)->assertJsonValidationErrors(['media']);
    }

    public function test_media_referenced_in_a_hero_block_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();
        Page::factory()->create([
            'content' => ['blocks' => [
                ['type' => 'hero', 'data' => ['title' => 'x', 'image_media_id' => $media->id]],
            ]],
        ]);

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")
            ->assertStatus(422)->assertJsonValidationErrors(['media']);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_media_referenced_in_an_image_block_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();
        Page::factory()->create([
            'content' => ['blocks' => [
                ['type' => 'image', 'data' => ['media_id' => $media->id]],
            ]],
        ]);

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")
            ->assertStatus(422)->assertJsonValidationErrors(['media']);
    }

    public function test_media_referenced_in_a_menu_item_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();
        Page::factory()->create([
            'content' => ['blocks' => [
                ['type' => 'menu', 'data' => ['items' => [
                    ['name' => 'Takoyaki', 'price' => '15000', 'image_media_id' => $media->id],
                ]]],
            ]],
        ]);

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")
            ->assertStatus(422)->assertJsonValidationErrors(['media']);
    }

    public function test_media_referenced_in_a_gallery_item_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();
        Page::factory()->create([
            'content' => ['blocks' => [
                ['type' => 'gallery', 'data' => ['items' => [
                    ['media_id' => $media->id, 'alt_text' => 'x'],
                ]]],
            ]],
        ]);

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")
            ->assertStatus(422)->assertJsonValidationErrors(['media']);
    }

    public function test_media_referenced_in_a_cover_or_couple_block_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $cover = Media::factory()->create();
        $person = Media::factory()->create();
        Page::factory()->create([
            'content' => ['blocks' => [
                ['type' => 'cover', 'data' => ['names' => 'A & B', 'image_media_id' => $cover->id]],
                ['type' => 'couple', 'data' => ['items' => [
                    ['name' => 'A', 'image_media_id' => null],
                    ['name' => 'B', 'image_media_id' => $person->id],
                ]]],
            ]],
        ]);

        foreach ([$cover, $person] as $media) {
            $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")
                ->assertStatus(422)->assertJsonValidationErrors(['media']);
        }
    }

    public function test_media_referenced_by_a_draft_page_cannot_be_deleted(): void
    {
        // Draft, not published — still must be protected, since it could
        // be published later and the image would otherwise already be gone.
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();
        $page = Page::factory()->create([
            'content' => ['blocks' => [
                ['type' => 'image', 'data' => ['media_id' => $media->id]],
            ]],
        ]);

        $this->assertSame('draft', $page->status->value);

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")
            ->assertStatus(422)->assertJsonValidationErrors(['media']);
    }

    public function test_cta_and_rich_text_blocks_do_not_block_unrelated_media_deletion(): void
    {
        // Confirms the reference check was actually inspected, not assumed
        // — cta/rich_text have no media field, so a page using them must
        // not accidentally block deletion of some unrelated media item.
        $admin = User::factory()->admin()->create();
        $media = Media::factory()->create();
        Page::factory()->create([
            'content' => ['blocks' => [
                ['type' => 'cta', 'data' => ['title' => 'x', 'button_label' => 'y', 'url' => '/z']],
                ['type' => 'rich_text', 'data' => ['body' => 'no media here']],
            ]],
        ]);

        $this->actingAs($admin)->deleteJson("/api/media/{$media->id}")->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_editor_cannot_delete_media(): void
    {
        $editor = User::factory()->create();
        $media = Media::factory()->create();

        $this->actingAs($editor)->deleteJson("/api/media/{$media->id}")->assertStatus(403);
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_guest_cannot_modify_media(): void
    {
        $media = Media::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->postJson('/api/media', ['file' => $file])->assertStatus(401);
        $this->patchJson("/api/media/{$media->id}", ['alt_text' => 'x'])->assertStatus(401);
        $this->deleteJson("/api/media/{$media->id}")->assertStatus(401);
    }
}
