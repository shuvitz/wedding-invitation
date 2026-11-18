<?php

namespace Tests\Feature\FormSubmissions;

use App\Models\FormSubmission;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function rsvpBlock(array $overrides = []): array
    {
        return ['type' => 'form', 'data' => array_merge([
            'form_key' => 'rsvp',
            'title' => 'RSVP',
            'success_message' => 'Terima kasih!',
            'fields' => [
                ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                ['name' => 'attendance', 'label' => 'Kehadiran', 'type' => 'select', 'required' => true, 'options' => ['Hadir', 'Tidak hadir']],
                ['name' => 'guests', 'label' => 'Jumlah tamu', 'type' => 'number', 'required' => false],
                ['name' => 'message', 'label' => 'Ucapan', 'type' => 'textarea', 'required' => false],
            ],
        ], $overrides)];
    }

    private function publishedPageWithForm(string $slug = 'wedding'): Page
    {
        return Page::factory()->published()->create([
            'slug' => $slug,
            'content' => ['blocks' => [$this->rsvpBlock()]],
        ]);
    }

    // ── Public submit ──────────────────────────────────

    public function test_visitor_can_submit_a_published_form(): void
    {
        $page = $this->publishedPageWithForm();

        $this->postJson('/api/public/pages/wedding/forms/rsvp', [
            'name' => 'Budi',
            'attendance' => 'Hadir',
            'guests' => 2,
            'message' => 'Selamat!',
        ])->assertCreated()->assertJsonPath('message', 'Terima kasih!');

        $submission = FormSubmission::sole();
        $this->assertSame($page->id, $submission->page_id);
        $this->assertSame('rsvp', $submission->form_key);
        $this->assertSame(['name' => 'Budi', 'attendance' => 'Hadir', 'guests' => 2, 'message' => 'Selamat!'], $submission->data);
    }

    public function test_undefined_fields_are_dropped(): void
    {
        $this->publishedPageWithForm();

        $this->postJson('/api/public/pages/wedding/forms/rsvp', [
            'name' => 'Budi',
            'attendance' => 'Hadir',
            'is_admin' => true,
        ])->assertCreated();

        $this->assertArrayNotHasKey('is_admin', FormSubmission::sole()->data);
    }

    public function test_submission_is_validated_against_the_block_fields(): void
    {
        $this->publishedPageWithForm();

        $this->postJson('/api/public/pages/wedding/forms/rsvp', [
            'attendance' => 'Maybe',
            'guests' => 'banyak',
            'message' => str_repeat('a', 5001),
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'attendance', 'guests', 'message']);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_validation_errors_are_in_indonesian_and_name_fields_by_their_label(): void
    {
        app()->setLocale('id');
        $this->publishedPageWithForm();

        $this->postJson('/api/public/pages/wedding/forms/rsvp', [
            'attendance' => 'Maybe',
            'guests' => 'banyak',
            'message' => str_repeat('a', 5001),
        ])->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nama wajib diisi.')
            ->assertJsonPath('errors.attendance.0', 'Pilihan Kehadiran tidak valid.')
            ->assertJsonPath('errors.guests.0', 'Jumlah tamu harus berupa angka.')
            ->assertJsonPath('errors.message.0', 'Ucapan maksimal 5000 karakter.')
            ->assertJsonPath('message', 'Nama wajib diisi. (dan 3 kesalahan lainnya)');
    }

    public function test_draft_page_missing_page_and_unknown_form_all_404(): void
    {
        Page::factory()->create(['slug' => 'draft', 'content' => ['blocks' => [$this->rsvpBlock()]]]);
        $this->publishedPageWithForm();
        $valid = ['name' => 'Budi', 'attendance' => 'Hadir'];

        $this->postJson('/api/public/pages/draft/forms/rsvp', $valid)->assertNotFound();
        $this->postJson('/api/public/pages/nope/forms/rsvp', $valid)->assertNotFound();
        $this->postJson('/api/public/pages/wedding/forms/other', $valid)->assertNotFound();

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_honeypot_submission_looks_successful_but_is_not_stored(): void
    {
        $this->publishedPageWithForm();

        $this->postJson('/api/public/pages/wedding/forms/rsvp', [
            'name' => 'Bot',
            'attendance' => 'Hadir',
            '_hp' => 'http://spam.example',
        ])->assertCreated();

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_submissions_are_rate_limited_per_ip(): void
    {
        $this->publishedPageWithForm();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/public/pages/wedding/forms/rsvp', ['name' => "Tamu {$i}", 'attendance' => 'Hadir'])->assertCreated();
        }

        $this->postJson('/api/public/pages/wedding/forms/rsvp', ['name' => 'Tamu 6', 'attendance' => 'Hadir'])->assertStatus(429);
        $this->assertDatabaseCount('form_submissions', 5);
    }

    public function test_cors_preflight_allows_cross_origin_post_from_the_frontend(): void
    {
        $this->publishedPageWithForm();

        $response = $this->call('OPTIONS', '/api/public/pages/wedding/forms/rsvp', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ]);

        $response->assertNoContent();
        $this->assertSame('http://localhost:3000', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('POST', $response->headers->get('Access-Control-Allow-Methods'));
    }

    public function test_public_page_exposes_form_definition(): void
    {
        $this->publishedPageWithForm();

        $this->getJson('/api/public/pages/wedding')
            ->assertOk()
            ->assertJsonPath('data.content.blocks.0.type', 'form')
            ->assertJsonPath('data.content.blocks.0.data.form_key', 'rsvp')
            ->assertJsonPath('data.content.blocks.0.data.fields.1.options', ['Hadir', 'Tidak hadir'])
            ->assertJsonPath('data.content.blocks.0.data.fields.2.required', false);
    }

    // ── Block validation (page editor) ─────────────────

    public function test_form_block_definition_is_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Bad Form',
            'content' => ['blocks' => [
                ['type' => 'form', 'data' => [
                    'form_key' => 'Not A Slug',
                    'fields' => [
                        ['name' => '_hp', 'label' => 'x', 'type' => 'text'],
                        ['name' => 'choice', 'label' => 'x', 'type' => 'select'],
                        ['name' => 'choice', 'label' => 'x', 'type' => 'file'],
                    ],
                ]],
                ['type' => 'form', 'data' => ['form_key' => 'empty', 'fields' => []]],
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'content.blocks.0.data.form_key',
            'content.blocks.0.data.fields.0.name',
            'content.blocks.0.data.fields.1.options',
            'content.blocks.0.data.fields.2.name',
            'content.blocks.0.data.fields.2.type',
            'content.blocks.1.data.fields',
        ]);
    }

    public function test_form_keys_must_be_unique_within_a_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/pages', [
            'title' => 'Two Forms',
            'content' => ['blocks' => [$this->rsvpBlock(), $this->rsvpBlock()]],
        ])->assertStatus(422)->assertJsonValidationErrors(['content.blocks']);
    }

    // ── Admin ──────────────────────────────────────────

    public function test_admin_can_list_filter_and_delete_submissions(): void
    {
        $admin = User::factory()->admin()->create();
        $page = $this->publishedPageWithForm();
        $other = $this->publishedPageWithForm('other');
        $rsvp = FormSubmission::create(['page_id' => $page->id, 'form_key' => 'rsvp', 'data' => ['name' => 'Budi']]);
        FormSubmission::create(['page_id' => $page->id, 'form_key' => 'contact', 'data' => ['name' => 'Ani']]);
        FormSubmission::create(['page_id' => $other->id, 'form_key' => 'rsvp', 'data' => ['name' => 'Elsewhere']]);

        $this->actingAs($admin)->getJson("/api/pages/{$page->id}/submissions")
            ->assertOk()->assertJsonCount(2, 'data');

        $this->actingAs($admin)->getJson("/api/pages/{$page->id}/submissions?form_key=rsvp")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.data.name', 'Budi');

        $this->actingAs($admin)->deleteJson("/api/submissions/{$rsvp->id}")->assertOk();
        $this->assertModelMissing($rsvp);
    }

    public function test_export_is_csv_with_union_columns_and_neutralized_formulas(): void
    {
        $admin = User::factory()->admin()->create();
        $page = $this->publishedPageWithForm();
        FormSubmission::create(['page_id' => $page->id, 'form_key' => 'rsvp', 'data' => ['name' => 'Budi', 'guests' => 2]]);
        FormSubmission::create(['page_id' => $page->id, 'form_key' => 'rsvp', 'data' => ['name' => '=HYPERLINK("http://evil")', 'message' => 'Hi']]);

        $response = $this->actingAs($admin)->get("/api/pages/{$page->id}/submissions/export");

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('submitted_at,form_key,', $csv);
        $this->assertStringContainsString('name', $csv);
        $this->assertStringContainsString('guests', $csv);
        $this->assertStringContainsString('message', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
    }

    public function test_editors_and_guests_cannot_see_or_delete_submissions(): void
    {
        $editor = User::factory()->create();
        $page = $this->publishedPageWithForm();
        $submission = FormSubmission::create(['page_id' => $page->id, 'form_key' => 'rsvp', 'data' => ['name' => 'Budi']]);

        $this->getJson("/api/pages/{$page->id}/submissions")->assertUnauthorized();
        $this->get("/api/pages/{$page->id}/submissions/export")->assertRedirect('/login');
        $this->actingAs($editor)->getJson("/api/pages/{$page->id}/submissions")->assertForbidden();
        $this->actingAs($editor)->get("/api/pages/{$page->id}/submissions/export")->assertForbidden();
        $this->actingAs($editor)->deleteJson("/api/submissions/{$submission->id}")->assertForbidden();
        $this->assertModelExists($submission);
    }

    public function test_deleting_a_page_deletes_its_submissions(): void
    {
        $page = $this->publishedPageWithForm();
        FormSubmission::create(['page_id' => $page->id, 'form_key' => 'rsvp', 'data' => ['name' => 'Budi']]);

        $page->delete();

        $this->assertDatabaseCount('form_submissions', 0);
    }
}
