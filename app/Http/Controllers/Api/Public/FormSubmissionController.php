<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\BlockType;
use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Models\Page;
use App\Services\BlockRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The public API's only write endpoint. Everything a submission may
 * contain comes from the `form` block stored on a *published* page — the
 * client only supplies values, never the field list or the rules. Rate
 * limited per IP (see AppServiceProvider's `form-submissions` limiter).
 */
class FormSubmissionController extends Controller
{
    public function store(Request $request, string $slug, string $formKey): JsonResponse
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('status', PageStatus::Published)
            ->firstOrFail();

        $form = collect($page->content['blocks'] ?? [])
            ->first(fn (array $block) => ($block['type'] ?? null) === BlockType::Form->value
                && ($block['data']['form_key'] ?? null) === $formKey)['data'] ?? null;

        abort_if($form === null, 404);

        $response = response()->json([
            'message' => ($form['success_message'] ?? null) ?: __('Thank you!'),
        ], 201);

        // Honeypot: a hidden input real visitors never fill. Bots get the
        // same success response so they don't learn to skip it.
        if ($request->filled('_hp')) {
            return $response;
        }

        // Errors name fields by the label guests see ("Jumlah Tamu"), not
        // the internal field name ("jumlah_tamu").
        $data = $request->validate(
            BlockRegistry::submissionRules($form['fields'] ?? []),
            [],
            collect($form['fields'] ?? [])->pluck('label', 'name')->all(),
        );

        FormSubmission::create([
            'page_id' => $page->id,
            'form_key' => $formKey,
            'data' => $data,
        ]);

        return $response;
    }
}
