<?php

namespace Tests\Unit\Policies;

use App\Models\Page;
use App\Models\User;
use App\Policies\PagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PagePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bypasses_every_ability_via_gate_before(): void
    {
        $admin = User::factory()->admin()->create();
        $published = Page::factory()->published()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Page::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Page::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $published));
        $this->assertTrue(Gate::forUser($admin)->allows('publish', $published));
        $this->assertTrue(Gate::forUser($admin)->allows('setHome', $published));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $published));
    }

    public function test_editor_can_view_and_create_pages(): void
    {
        $editor = User::factory()->create();

        $this->assertTrue((new PagePolicy)->viewAny($editor));
        $this->assertTrue((new PagePolicy)->create($editor));
    }

    public function test_editor_can_update_a_draft_page(): void
    {
        $editor = User::factory()->create();
        $draft = Page::factory()->create();

        $this->assertTrue((new PagePolicy)->update($editor, $draft));
    }

    public function test_editor_cannot_update_a_published_page(): void
    {
        $editor = User::factory()->create();
        $published = Page::factory()->published()->create();

        $this->assertFalse((new PagePolicy)->update($editor, $published));
    }

    public function test_editor_cannot_publish_a_page(): void
    {
        $editor = User::factory()->create();
        $draft = Page::factory()->create();
        $published = Page::factory()->published()->create();

        $policy = new PagePolicy;

        $this->assertFalse($policy->publish($editor, $draft));
        $this->assertFalse($policy->publish($editor, $published));
    }

    public function test_editor_cannot_set_a_page_as_home(): void
    {
        $editor = User::factory()->create();
        $published = Page::factory()->published()->create();

        $this->assertFalse((new PagePolicy)->setHome($editor, $published));
    }

    public function test_editor_cannot_delete_a_page(): void
    {
        $editor = User::factory()->create();
        $page = Page::factory()->create();

        $this->assertFalse((new PagePolicy)->delete($editor, $page));
    }

    public function test_policy_is_auto_discovered_through_the_gate(): void
    {
        $editor = User::factory()->create();
        $published = Page::factory()->published()->create();

        $this->assertTrue(Gate::forUser($editor)->denies('publish', $published));
        $this->assertTrue(Gate::forUser($editor)->denies('update', $published));
    }
}
