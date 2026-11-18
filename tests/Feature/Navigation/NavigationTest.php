<?php

namespace Tests\Feature\Navigation;

use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_navigation_items(): void
    {
        $admin = User::factory()->admin()->create();
        NavigationItem::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/navigation');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_editor_can_list_navigation_items(): void
    {
        $editor = User::factory()->create();
        NavigationItem::factory()->create();

        $this->actingAs($editor)->getJson('/api/navigation')->assertOk();
    }

    public function test_guest_cannot_list_navigation_items(): void
    {
        $this->getJson('/api/navigation')->assertStatus(401);
    }

    public function test_navigation_items_are_returned_in_order(): void
    {
        $admin = User::factory()->admin()->create();
        NavigationItem::factory()->create(['label' => 'Third', 'order' => 3]);
        NavigationItem::factory()->create(['label' => 'First', 'order' => 1]);
        NavigationItem::factory()->create(['label' => 'Second', 'order' => 2]);

        $response = $this->actingAs($admin)->getJson('/api/navigation');

        $response->assertOk()
            ->assertJsonPath('data.0.label', 'First')
            ->assertJsonPath('data.1.label', 'Second')
            ->assertJsonPath('data.2.label', 'Third');
    }

    public function test_admin_can_create_navigation_item(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/navigation', [
            'label' => 'About',
            'url'   => '/about',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.label', 'About')
            ->assertJsonPath('data.url', '/about')
            ->assertJsonPath('data.visible', true);

        $this->assertDatabaseHas('navigation_items', ['label' => 'About', 'url' => '/about']);
    }

    public function test_new_navigation_item_is_appended_to_the_end_of_the_order_by_default(): void
    {
        $admin = User::factory()->admin()->create();
        NavigationItem::factory()->create(['order' => 5]);

        $response = $this->actingAs($admin)->postJson('/api/navigation', [
            'label' => 'Contact',
            'url'   => '/contact',
        ]);

        $response->assertCreated()->assertJsonPath('data.order', 6);
    }

    public function test_editor_cannot_create_navigation_item(): void
    {
        $editor = User::factory()->create();

        $this->actingAs($editor)->postJson('/api/navigation', [
            'label' => 'About',
            'url'   => '/about',
        ])->assertStatus(403);
    }

    public function test_create_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/navigation', [])
            ->assertStatus(422)->assertJsonValidationErrors(['label', 'url']);
    }

    public function test_admin_can_update_navigation_item(): void
    {
        $admin = User::factory()->admin()->create();
        $item = NavigationItem::factory()->create(['label' => 'Old']);

        $response = $this->actingAs($admin)->putJson("/api/navigation/{$item->id}", [
            'label' => 'New',
            'url'   => $item->url,
        ]);

        $response->assertOk()->assertJsonPath('data.label', 'New');
    }

    public function test_editor_cannot_update_navigation_item(): void
    {
        $editor = User::factory()->create();
        $item = NavigationItem::factory()->create();

        $this->actingAs($editor)->putJson("/api/navigation/{$item->id}", [
            'label' => 'Hijacked',
        ])->assertStatus(403);
    }

    public function test_admin_can_toggle_navigation_item_visibility(): void
    {
        $admin = User::factory()->admin()->create();
        $item = NavigationItem::factory()->create(['visible' => true]);

        $response = $this->actingAs($admin)->patchJson("/api/navigation/{$item->id}/visibility", [
            'visible' => false,
        ]);

        $response->assertOk()->assertJsonPath('data.visible', false);
        $this->assertFalse($item->fresh()->visible);
    }

    public function test_editor_cannot_toggle_navigation_item_visibility(): void
    {
        $editor = User::factory()->create();
        $item = NavigationItem::factory()->create();

        $this->actingAs($editor)->patchJson("/api/navigation/{$item->id}/visibility", [
            'visible' => false,
        ])->assertStatus(403);
    }

    public function test_visibility_update_validates_boolean(): void
    {
        $admin = User::factory()->admin()->create();
        $item = NavigationItem::factory()->create();

        $this->actingAs($admin)->patchJson("/api/navigation/{$item->id}/visibility", [
            'visible' => 'not-a-boolean',
        ])->assertStatus(422)->assertJsonValidationErrors(['visible']);
    }

    public function test_admin_can_delete_navigation_item(): void
    {
        $admin = User::factory()->admin()->create();
        $item = NavigationItem::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/navigation/{$item->id}")->assertOk();

        $this->assertDatabaseMissing('navigation_items', ['id' => $item->id]);
    }

    public function test_editor_cannot_delete_navigation_item(): void
    {
        $editor = User::factory()->create();
        $item = NavigationItem::factory()->create();

        $this->actingAs($editor)->deleteJson("/api/navigation/{$item->id}")->assertStatus(403);

        $this->assertDatabaseHas('navigation_items', ['id' => $item->id]);
    }

    public function test_guest_cannot_modify_navigation(): void
    {
        $item = NavigationItem::factory()->create();

        $this->postJson('/api/navigation', ['label' => 'x', 'url' => '/x'])->assertStatus(401);
        $this->putJson("/api/navigation/{$item->id}", ['label' => 'x'])->assertStatus(401);
        $this->patchJson("/api/navigation/{$item->id}/visibility", ['visible' => false])->assertStatus(401);
        $this->deleteJson("/api/navigation/{$item->id}")->assertStatus(401);
    }
}
