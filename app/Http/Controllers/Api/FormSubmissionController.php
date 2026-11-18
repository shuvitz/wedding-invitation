<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FormSubmissions\DestroyFormSubmissionRequest;
use App\Http\Requests\FormSubmissions\IndexFormSubmissionRequest;
use App\Http\Resources\FormSubmissionResource;
use App\Models\FormSubmission;
use App\Models\Page;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormSubmissionController extends Controller
{
    /**
     * A page's submissions, newest first, optionally narrowed to one form.
     */
    public function index(IndexFormSubmissionRequest $request, Page $page): AnonymousResourceCollection
    {
        $submissions = $this->query($request, $page)->paginate($request->integer('per_page', 50));

        return FormSubmissionResource::collection($submissions);
    }

    /**
     * The same list as CSV. Columns are the union of every submission's
     * keys, so rows submitted before a form's fields changed still export.
     */
    public function export(IndexFormSubmissionRequest $request, Page $page): StreamedResponse
    {
        $submissions = $this->query($request, $page)->get();
        $columns = $submissions->flatMap(fn (FormSubmission $s) => array_keys($s->data))->unique()->values()->all();

        return response()->streamDownload(function () use ($submissions, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM, so Excel reads UTF-8 names correctly
            fputcsv($out, ['submitted_at', 'form_key', ...$columns]);

            foreach ($submissions as $submission) {
                fputcsv($out, [
                    $submission->created_at->toDateTimeString(),
                    $submission->form_key,
                    ...array_map(fn ($column) => Csv::cell($submission->data[$column] ?? ''), $columns),
                ]);
            }

            fclose($out);
        }, "{$page->slug}-submissions.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(DestroyFormSubmissionRequest $request, FormSubmission $submission): JsonResponse
    {
        $submission->delete();

        return response()->json([
            'data'    => null,
            'message' => 'ok',
        ]);
    }

    private function query(IndexFormSubmissionRequest $request, Page $page): Builder
    {
        return FormSubmission::query()
            ->where('page_id', $page->id)
            ->when($request->filled('form_key'), fn ($query) => $query->where('form_key', $request->string('form_key')))
            ->latest()
            ->orderByDesc('id');
    }
}
