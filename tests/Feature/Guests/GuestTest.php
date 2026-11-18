<?php

namespace Tests\Feature\Guests;

use App\Models\FormSubmission;
use App\Models\Guest;
use App\Models\Page;
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

    public function test_whatsapp_link_uses_an_international_number_and_carries_the_invitation(): void
    {
        config(['app.frontend_url' => 'https://undangan.example']);

        $local = Guest::factory()->make(['name' => 'Budi', 'phone' => '0812-3456 7890']);
        $international = Guest::factory()->make(['name' => 'Ani', 'phone' => '+65 9123 4567']);
        $noPhone = Guest::factory()->make(['name' => 'Citra', 'phone' => null]);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $local->whatsappUrl());
        $this->assertStringStartsWith('https://wa.me/6591234567?text=', $international->whatsappUrl());
        $this->assertStringStartsWith('https://wa.me/?text=', $noPhone->whatsappUrl());

        parse_str(parse_url($local->whatsappUrl(), PHP_URL_QUERY), $query);
        $this->assertStringContainsString('Kepada Yth. Budi,', $query['text']);
        $this->assertStringContainsString('https://undangan.example/?to=Budi', $query['text']);
    }

    public function test_guest_list_shows_each_guests_latest_reply(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->published()->create();
        Guest::factory()->create(['name' => 'Budi Santoso']);
        Guest::factory()->create(['name' => 'Citra']);
        $reply = fn (string $to, string $attendance) => FormSubmission::create([
            'page_id' => $page->id, 'form_key' => 'rsvp', 'invited_as' => $to, 'data' => ['kehadiran' => $attendance],
        ]);
        $reply('Budi Santoso', 'Tidak Hadir');
        $reply('Budi Santoso', 'Hadir');
        $reply('Someone Else', 'Hadir');

        $this->actingAs($admin)->getJson('/api/guests')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Budi Santoso')
            ->assertJsonPath('data.0.reply.data.kehadiran', 'Hadir')
            ->assertJsonPath('data.1.name', 'Citra')
            ->assertJsonPath('data.1.reply', null);
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
