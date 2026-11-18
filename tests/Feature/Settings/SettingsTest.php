<?php

namespace Tests\Feature\Settings;

use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson('/api/settings');

        $response->assertOk()->assertJsonStructure([
            'data' => ['site_identity', 'contact', 'social', 'seo', 'updated_at'],
        ]);
    }

    public function test_editor_can_read_settings(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->getJson('/api/settings')->assertOk();
    }

    public function test_guest_cannot_read_settings(): void
    {
        $this->getJson('/api/settings')->assertStatus(401);
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->putJson('/api/settings', [
            'site_identity' => ['name' => 'Denji CMS'],
            'contact'       => ['email' => 'hello@denji.test'],
            'social'        => ['instagram' => '@denjicms'],
            'seo'           => ['meta_title' => 'Denji CMS — Websites made simple'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.site_identity.name', 'Denji CMS')
            ->assertJsonPath('data.contact.email', 'hello@denji.test')
            ->assertJsonPath('data.social.instagram', '@denjicms')
            ->assertJsonPath('data.seo.meta_title', 'Denji CMS — Websites made simple');

        $this->assertDatabaseHas('settings', ['id' => Setting::current()->id]);
    }

    public function test_updating_one_group_does_not_wipe_another(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson('/api/settings', [
            'site_identity' => ['name' => 'Denji CMS'],
        ])->assertOk();

        $response = $this->actingAs($admin)->putJson('/api/settings', [
            'contact' => ['email' => 'hello@denji.test'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.site_identity.name', 'Denji CMS')
            ->assertJsonPath('data.contact.email', 'hello@denji.test');
    }

    public function test_updating_one_field_in_a_group_preserves_sibling_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson('/api/settings', [
            'contact' => ['email' => 'hello@denji.test', 'phone' => '+62 812 0000 0000'],
        ])->assertOk();

        $response = $this->actingAs($admin)->putJson('/api/settings', [
            'contact' => ['phone' => '+62 812 1111 1111'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.contact.email', 'hello@denji.test')
            ->assertJsonPath('data.contact.phone', '+62 812 1111 1111');
    }

    public function test_editor_cannot_update_settings(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->putJson('/api/settings', [
            'site_identity' => ['name' => 'Hijacked'],
        ])->assertStatus(403);
    }

    public function test_guest_cannot_update_settings(): void
    {
        $this->putJson('/api/settings', ['site_identity' => ['name' => 'x']])->assertStatus(401);
    }

    public function test_update_validates_email_format(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson('/api/settings', [
            'contact' => ['email' => 'not-an-email'],
        ])->assertStatus(422)->assertJsonValidationErrors(['contact.email']);
    }

    public function test_update_validates_field_length(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson('/api/settings', [
            'site_identity' => ['name' => str_repeat('a', 300)],
        ])->assertStatus(422)->assertJsonValidationErrors(['site_identity.name']);
    }

    public function test_settings_can_reference_selected_media(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $logo = Media::factory()->create();

        $response = $this->actingAs($admin)->putJson('/api/settings', [
            'site_identity' => ['logo_media_id' => $logo->id],
        ]);

        $response->assertOk()->assertJsonPath('data.site_identity.logo.id', $logo->id);

        $this->assertSame($logo->id, Setting::current()->site_identity['logo_media_id']);
    }

    public function test_update_rejects_a_nonexistent_media_id(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson('/api/settings', [
            'site_identity' => ['logo_media_id' => 999999],
        ])->assertStatus(422)->assertJsonValidationErrors(['site_identity.logo_media_id']);
    }

    public function test_settings_response_resolves_media_url_correctly(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $logo = Media::factory()->create(['alt_text' => 'Company logo']);

        $setting = Setting::current();
        $setting->site_identity = array_merge($setting->site_identity, ['logo_media_id' => $logo->id]);
        $setting->save();

        $response = $this->actingAs($admin)->getJson('/api/settings');

        $expectedUrl = Storage::disk($logo->disk)->url($logo->path);

        $response->assertOk()
            ->assertJsonPath('data.site_identity.logo.id', $logo->id)
            ->assertJsonPath('data.site_identity.logo.url', $expectedUrl)
            ->assertJsonPath('data.site_identity.logo.alt_text', 'Company logo')
            ->assertJsonPath('data.site_identity.favicon', null);
    }

    public function test_settings_expose_a_default_null_whatsapp_field(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('data.social.whatsapp', null);
    }

    public function test_admin_can_set_the_whatsapp_field(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->putJson('/api/settings', [
            'social' => ['whatsapp' => 'https://wa.me/6281200000000'],
        ]);

        $response->assertOk()->assertJsonPath('data.social.whatsapp', 'https://wa.me/6281200000000');
        $this->assertSame('https://wa.me/6281200000000', Setting::current()->social['whatsapp']);
    }

    public function test_setting_whatsapp_does_not_wipe_other_social_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->putJson('/api/settings', [
            'social' => ['instagram' => '@denjicms'],
        ])->assertOk();

        $response = $this->actingAs($admin)->putJson('/api/settings', [
            'social' => ['whatsapp' => 'https://wa.me/6281200000000'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.social.instagram', '@denjicms')
            ->assertJsonPath('data.social.whatsapp', 'https://wa.me/6281200000000');
    }

    public function test_editor_cannot_set_the_whatsapp_field(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->putJson('/api/settings', [
            'social' => ['whatsapp' => 'https://wa.me/6281200000000'],
        ])->assertStatus(403);
    }

    public function test_public_settings_endpoint_exposes_whatsapp(): void
    {
        $setting = Setting::current();
        $setting->social = array_merge($setting->social, ['whatsapp' => 'https://wa.me/6281200000000']);
        $setting->save();

        $this->getJson('/api/public/settings')
            ->assertOk()
            ->assertJsonPath('data.social.whatsapp', 'https://wa.me/6281200000000');
    }
}
