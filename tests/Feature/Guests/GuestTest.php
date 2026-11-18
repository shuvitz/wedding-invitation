<?php

namespace Tests\Feature\Guests;

use App\Models\FormSubmission;
use App\Models\Guest;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $excelStripped = Guest::factory()->make(['name' => 'Dodi', 'phone' => '81234567890']);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $excelStripped->whatsappUrl());

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

    public function test_admin_can_edit_and_reset_the_whatsapp_message(): void
    {
        config(['app.frontend_url' => 'https://undangan.example']);
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->create();

        $this->actingAs($admin)->putJson('/api/guest-message', ['message' => 'Halo {nama}'])
            ->assertStatus(422)->assertJsonValidationErrors(['message']);
        $this->actingAs($editor)->putJson('/api/guest-message', ['message' => 'Halo {link}'])
            ->assertForbidden();

        $this->actingAs($admin)->putJson('/api/guest-message', ['message' => 'Halo {nama}, ini undangannya: {link}'])
            ->assertOk()->assertJsonPath('data.message_template', 'Halo {nama}, ini undangannya: {link}');

        $url = Guest::factory()->make(['name' => 'Budi'])->whatsappUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('Halo Budi, ini undangannya: https://undangan.example/?to=Budi', $query['text']);

        $this->actingAs($admin)->putJson('/api/guest-message', ['message' => null])
            ->assertOk()->assertJsonPath('data.message_template', Guest::DEFAULT_MESSAGE);
    }

    public function test_admin_can_import_guests_from_csv(): void
    {
        $admin = User::factory()->admin()->create();
        Guest::factory()->create(['name' => 'Budi Santoso']);

        $csv = "\xEF\xBB\xBFNama;No HP;Catatan\r\n"
            ."Ani Wijaya;0812-1111-2222;Teman kuliah\r\n"
            ."budi santoso;;\r\n"                       // already on the list
            ."\"Citra; & Keluarga\";;Keluarga\r\n"      // quoted delimiter
            .";0812;\r\n"                               // no name
            ."Dodi;call me;\r\n"                        // bad phone
            ."\r\n"
            ."Ani Wijaya;;\r\n";                        // repeated within the file

        $this->actingAs($admin)
            ->post('/api/guests/import', ['file' => UploadedFile::fake()->createWithContent('tamu.csv', $csv)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.created', 2)
            ->assertJsonPath('data.duplicates', 2)
            ->assertJsonPath('data.errors.0.row', 5)
            ->assertJsonPath('data.errors.1.row', 6);

        $this->assertSame(['Ani Wijaya', 'Budi Santoso', 'Citra; & Keluarga'], Guest::orderBy('name')->pluck('name')->all());
        $this->assertSame('0812-1111-2222', Guest::where('name', 'Ani Wijaya')->value('phone'));
    }

    public function test_import_without_header_and_comma_separated(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->create();
        $file = fn () => UploadedFile::fake()->createWithContent('tamu.csv', "Ani,0812,\nBudi\n");

        $this->actingAs($editor)->post('/api/guests/import', ['file' => $file()], ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs($admin)->post('/api/guests/import', ['file' => $file()], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.created', 2);
    }

    public function test_export_lists_guests_with_links_and_replies_and_imports_back(): void
    {
        config(['app.frontend_url' => 'https://undangan.example']);
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->published()->create();
        Guest::factory()->create(['name' => 'Budi', 'phone' => '+62 812-0000-0000', 'notes' => 'Kantor']);
        Guest::factory()->create(['name' => '=HYPERLINK("http://evil")']);
        FormSubmission::create(['page_id' => $page->id, 'form_key' => 'rsvp', 'invited_as' => 'budi', 'data' => ['nama' => 'Budi', 'kehadiran' => 'Hadir', 'jumlah_tamu' => 2]]);

        $response = $this->actingAs($admin)->get('/api/guests/export');
        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString("name,phone,notes,invitation_link,replied_at,reply\n", $csv);
        $this->assertStringContainsString("Budi,\"'+62 812-0000-0000\",Kantor,https://undangan.example/?to=Budi,", $csv);
        $this->assertStringContainsString(',"Hadir · 2"', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);

        Guest::query()->delete();
        $this->actingAs($admin)
            ->post('/api/guests/import', ['file' => UploadedFile::fake()->createWithContent('tamu.csv', $csv)], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.created', 2);
        $this->assertSame('+62 812-0000-0000', Guest::where('name', 'Budi')->value('phone'));
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
        $this->actingAs($editor)->get('/api/guests/export')->assertForbidden();
        $this->assertModelExists($guest);
    }
}
