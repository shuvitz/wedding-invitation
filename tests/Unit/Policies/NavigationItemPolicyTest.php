<?php

namespace Tests\Unit\Policies;

use App\Models\NavigationItem;
use App\Models\User;
use App\Policies\NavigationItemPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class NavigationItemPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bypasses_every_ability_via_gate_before(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', NavigationItem::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', NavigationItem::class));
    }

    public function test_editor_can_view_navigation(): void
    {
        $editor = User::factory()->create();

        $this->assertTrue((new NavigationItemPolicy)->viewAny($editor));
    }

    public function test_editor_cannot_create_update_or_delete_navigation(): void
    {
        $editor = User::factory()->create();
        $item = NavigationItem::factory()->create();

        $policy = new NavigationItemPolicy;

        $this->assertFalse($policy->create($editor));
        $this->assertFalse($policy->update($editor, $item));
        $this->assertFalse($policy->delete($editor, $item));
    }

    public function test_policy_is_auto_discovered_through_the_gate(): void
    {
        $editor = User::factory()->create();
        $item = NavigationItem::factory()->create();

        $this->assertTrue(Gate::forUser($editor)->denies('update', $item));
    }
}
