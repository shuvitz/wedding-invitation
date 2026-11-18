<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_admin_can_search_users_by_name_or_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Jane Roe', 'email' => 'jane@example.com']);
        User::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);

        $response = $this->actingAs($admin)->getJson('/api/users?search=jane');

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'jane@example.com');
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create(); // editors

        $response = $this->actingAs($admin)->getJson('/api/users?role=admin');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_editor_cannot_list_users(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->getJson('/api/users')->assertStatus(403);
    }

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/users')->assertStatus(401);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name'     => 'New Editor',
            'email'    => 'new.editor@example.com',
            'password' => 'a-reasonably-strong-password',
            'role'     => 'editor',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'new.editor@example.com')
            ->assertJsonPath('data.role', 'editor')
            ->assertJsonPath('data.active', true)
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', ['email' => 'new.editor@example.com', 'role' => 'editor']);
    }

    public function test_editor_cannot_create_user(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->postJson('/api/users', [
            'name'     => 'New Editor',
            'email'    => 'new.editor@example.com',
            'password' => 'a-reasonably-strong-password',
            'role'     => 'editor',
        ])->assertStatus(403);
    }

    public function test_create_user_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/users', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_create_user_validates_unique_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)->postJson('/api/users', [
            'name'     => 'Someone',
            'email'    => 'taken@example.com',
            'password' => 'a-reasonably-strong-password',
            'role'     => 'editor',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_create_user_validates_role_is_a_valid_enum_value(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/users', [
            'name'     => 'Someone',
            'email'    => 'someone@example.com',
            'password' => 'a-reasonably-strong-password',
            'role'     => 'superadmin',
        ])->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_admin_can_update_user_name_and_email(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin)->putJson("/api/users/{$user->id}", [
            'name'  => 'New Name',
            'email' => $user->email,
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_admin_can_change_a_users_role(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/users/{$editor->id}", [
            'role' => 'admin',
        ]);

        $response->assertOk()->assertJsonPath('data.role', 'admin');
        $this->assertSame('admin', $editor->fresh()->role->value);
    }

    public function test_editor_cannot_update_users(): void
    {
        $editor = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($editor)->putJson("/api/users/{$other->id}", [
            'name' => 'Hijacked',
        ])->assertStatus(403);
    }

    public function test_editor_cannot_self_promote_via_update_endpoint(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->putJson("/api/users/{$editor->id}", [
            'role' => 'admin',
        ])->assertStatus(403);

        $this->assertSame('editor', $editor->fresh()->role->value);
    }

    public function test_admin_can_deactivate_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->patchJson("/api/users/{$user->id}/status", [
            'active' => false,
        ]);

        $response->assertOk()->assertJsonPath('data.active', false);
        $this->assertFalse($user->fresh()->active);
    }

    public function test_admin_can_reactivate_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->inactive()->create();

        $response = $this->actingAs($admin)->patchJson("/api/users/{$user->id}/status", [
            'active' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.active', true);
    }

    public function test_editor_cannot_change_user_status(): void
    {
        $editor = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($editor)->patchJson("/api/users/{$other->id}/status", [
            'active' => false,
        ])->assertStatus(403);
    }

    public function test_admin_cannot_demote_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->putJson("/api/users/{$admin->id}", [
            'role' => 'editor',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['role']);
        $this->assertSame('admin', $admin->fresh()->role->value);
    }

    public function test_admin_cannot_deactivate_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patchJson("/api/users/{$admin->id}/status", [
            'active' => false,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['active']);
        $this->assertTrue($admin->fresh()->active);
    }

    public function test_admin_can_demote_an_admin_when_another_active_admin_remains(): void
    {
        $admin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->putJson("/api/users/{$secondAdmin->id}", [
            'role' => 'editor',
        ]);

        $response->assertOk()->assertJsonPath('data.role', 'editor');
    }

    public function test_admin_can_deactivate_an_admin_when_another_active_admin_remains(): void
    {
        $admin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patchJson("/api/users/{$secondAdmin->id}/status", [
            'active' => false,
        ]);

        $response->assertOk()->assertJsonPath('data.active', false);
    }

    public function test_an_inactive_admin_does_not_block_a_role_change_on_the_only_active_admin(): void
    {
        // An inactive admin can't actually administer anything, so they
        // shouldn't count toward "another admin remains".
        User::factory()->admin()->inactive()->create();
        $activeAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($activeAdmin)->putJson("/api/users/{$activeAdmin->id}", [
            'role' => 'editor',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['role']);
    }
}
