<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use Database\Seeders\DemoInvitationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoInvitationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_a_published_homepage_and_is_safe_to_run_again(): void
    {
        $oldHome = Page::factory()->published()->create(['is_home' => true]);

        $this->seed(DemoInvitationSeeder::class);
        $this->seed(DemoInvitationSeeder::class);

        $this->assertSame(1, Page::where('slug', DemoInvitationSeeder::SLUG)->count());
        $this->assertFalse($oldHome->fresh()->is_home);
        $this->assertSame('Raka & Sekar', Setting::current()->site_identity['name']);

        $this->getJson('/api/public/home')
            ->assertOk()
            ->assertJsonPath('data.slug', DemoInvitationSeeder::SLUG)
            ->assertJsonPath('data.content.blocks.0.type', 'cover')
            ->assertJsonCount(7, 'data.content.blocks');
    }
}
