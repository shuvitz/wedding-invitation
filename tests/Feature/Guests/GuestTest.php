<?php

namespace Tests\Feature\Guests;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_list_search_update_and_delete_guests(): void
    {
        config(['app.frontend_url' => 'https://undangan.example/']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/guests', ['name' => 'Budi Santoso & Keluarga', 'phone' => '+62 812-0000-0000', 'notes' => 'Teman kantor'])
            ->assertCreated()
            ->assertJsonPath('data.invitation_url', 'https://undangan.example/?to=Budi+Santoso+%26+Keluarga');
        Guest::factory()->create(['name' => 'Ani']);

        $this->actingAs($admin)->getJson('/api/guests')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.name', 'Ani');
        $this->actingAs($admin)->getJson('/api/guests?search=budi')
            ->assertOk()->assertJsonCount(1, 'data');

        $budi = Guest::where('name', 'like', 'Budi%')->sole();
        $this->actingAs($admin)->putJson("/api/guests/{$budi->id}", ['name' => 'Budi', 'phone' => null])
            ->assertOk()->assertJsonPath('data.name', 'Budi')->assertJsonPath('data.phone', null);

        $this->actingAs($admin)->deleteJson("/api/guests/{$budi->id}")->assertOk();
        $this->assertModelMissing($budi);
    }

    public function test_guest_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/guests', ['name' => str_repeat('a', 101), 'phone' => 'call me'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'phone']);
    }

    public function test_editors_and_visitors_cannot_access_the_guest_list(): void
    {
        $editor = User::factory()->create();
        $guest = Guest::factory()->create();

        $this->getJson('/api/guests')->assertUnauthorized();
        $this->actingAs($editor)->getJson('/api/guests')->assertForbidden();
        $this->actingAs($editor)->postJson('/api/guests', ['name' => 'X'])->assertForbidden();
        $this->actingAs($editor)->putJson("/api/guests/{$guest->id}", ['name' => 'X'])->assertForbidden();
        $this->actingAs($editor)->deleteJson("/api/guests/{$guest->id}")->assertForbidden();
        $this->assertModelExists($guest);
    }
}
