<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bypasses_every_ability_via_gate_before(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('some-ability-that-does-not-exist'));
    }

    public function test_editor_cannot_view_any_users(): void
    {
        $editor = User::factory()->create();

        $this->assertFalse((new UserPolicy)->viewAny($editor));
    }

    public function test_policy_is_auto_discovered_through_the_gate(): void
    {
        $editor = User::factory()->create();

        $this->assertTrue(Gate::forUser($editor)->denies('viewAny', User::class));
    }

    public function test_editor_cannot_create_users(): void
    {
        $editor = User::factory()->create();

        $this->assertFalse((new UserPolicy)->create($editor));
    }

    /**
     * Phase 0 gave non-admins a "view/update yourself" exception. Phase 1
     * removes it: the update endpoint now also changes `role`, so an
     * editor passing this check for their own account would be able to
     * self-promote to admin. See UserPolicy's class docblock.
     */
    public function test_editor_cannot_view_or_update_even_themselves(): void
    {
        $editor = User::factory()->create();

        $policy = new UserPolicy;

        $this->assertFalse($policy->view($editor, $editor));
        $this->assertFalse($policy->update($editor, $editor));
    }

    public function test_editor_cannot_view_or_update_another_user(): void
    {
        $editor = User::factory()->create();
        $otherUser = User::factory()->create();

        $policy = new UserPolicy;

        $this->assertFalse($policy->view($editor, $otherUser));
        $this->assertFalse($policy->update($editor, $otherUser));
    }

    public function test_editor_can_never_delete_a_user(): void
    {
        $editor = User::factory()->create();
        $otherUser = User::factory()->create();

        $policy = new UserPolicy;

        $this->assertFalse($policy->delete($editor, $otherUser));
        $this->assertFalse($policy->delete($editor, $editor));
    }
}
