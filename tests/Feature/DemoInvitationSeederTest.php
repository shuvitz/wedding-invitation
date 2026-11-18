<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use Database\Seeders\DemoInvitationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoInvitationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_a_published_homepage_and_is_safe_to_run_again(): void
    {
        Storage::fake('public');
        $oldHome = Page::factory()->published()->create(['is_home' => true]);

        $this->seed(DemoInvitationSeeder::class);
        $this->seed(DemoInvitationSeeder::class);

        $this->assertSame(6, Media::count());
        Storage::disk('public')->assertExists('media/demo/cover.jpg');
        $this->assertSame(1, Page::where('slug', DemoInvitationSeeder::SLUG)->count());
        $this->assertFalse($oldHome->fresh()->is_home);
        $this->assertSame('Raka & Sekar', Setting::current()->site_identity['name']);

        $this->getJson('/api/public/home')
            ->assertOk()
            ->assertJsonPath('data.slug', DemoInvitationSeeder::SLUG)
            ->assertJsonPath('data.content.blocks.0.type', 'cover')
            ->assertJsonPath('data.content.blocks.0.data.image_url', Storage::disk('public')->url('media/demo/cover.jpg'))
            ->assertJsonCount(8, 'data.content.blocks');
    }
}
