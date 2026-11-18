<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guests\SaveGuestRequest;
use App\Http\Resources\GuestResource;
use App\Models\FormSubmission;
use App\Models\Guest;
use App\Models\Setting;
use App\Support\Csv;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestController extends Controller
{
    /**
     * All guests, alphabetical, optionally filtered by name.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('manage', Guest::class);

        // shortcut: not paginated — a wedding list is a few hundred rows; paginate past ~2000.
        $guests = Guest::query()
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->get();

        return GuestResource::collection($this->withReplies($guests))
            ->additional(['meta' => ['message_template' => Guest::messageTemplate()]]);
    }

    /**
     * The whole list as CSV. The first three columns match the import
     * format, so an exported file can be edited and imported elsewhere.
     */
    public function export(): StreamedResponse
    {
        Gate::authorize('manage', Guest::class);

        $guests = $this->withReplies(Guest::query()->orderBy('name')->get());

        return response()->streamDownload(function () use ($guests) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM, so Excel reads UTF-8 names correctly
            fputcsv($out, ['name', 'phone', 'notes', 'invitation_link', 'replied_at', 'reply']);

            foreach ($guests as $guest) {
                // Answers in form order, minus the guest's own name (already in the first column).
                $answers = collect($guest->reply?->data ?? [])
                    ->reject(fn ($value) => $value === null || $value === '' || mb_strtolower((string) $value) === mb_strtolower($guest->name))
                    ->implode(' · ');

                fputcsv($out, array_map(Csv::cell(...), [
                    $guest->name,
                    $guest->phone,
                    $guest->notes,
                    $guest->invitationUrl(),
                    $guest->reply?->created_at->toDateTimeString(),
                    $answers,
                ]));
            }

            fclose($out);
        }, 'daftar-tamu.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Sets each guest's latest reply (or null) as the `reply` relation,
     * matched on the `?to=` name their link carried (case-insensitive).
     *
     * @param  Collection<int, Guest>  $guests
     * @return Collection<int, Guest>
     */
    private function withReplies(Collection $guests): Collection
    {
        // shortcut: renaming a guest unlinks replies sent under the old name; store a guest token in the link if that matters.
        $key = fn (string $name) => mb_strtolower($name);
        $replies = FormSubmission::query()
            ->whereIn(DB::raw('LOWER(invited_as)'), $guests->map(fn (Guest $guest) => $key($guest->name)))
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->unique(fn (FormSubmission $reply) => $key($reply->invited_as))
            ->keyBy(fn (FormSubmission $reply) => $key($reply->invited_as));

        return $guests->each(fn (Guest $guest) => $guest->setRelation('reply', $replies->get($key($guest->name))));
    }

    /**
     * Add guests from a CSV of name, phone, notes (only name required), with
     * or without a header row, comma- or semicolon-separated (Excel in an
     * Indonesian locale saves with ";"). Invalid rows are reported, names
     * already on the list are skipped, and the rest are added together.
     */
    public function import(Request $request): JsonResponse
    {
        Gate::authorize('manage', Guest::class);

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']]);

        // shortcut: splits on newlines before parsing, so a quoted cell containing a line break breaks that row; use a stream CSV reader if notes ever span lines.
        $lines = preg_split('/\r\n|\r|\n/', preg_replace('/^\xEF\xBB\xBF/', '', $request->file('file')->get()));
        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';

        $existing = Guest::pluck('name')->map(fn (string $name) => mb_strtolower($name))->flip();
        $rows = [];
        $errors = [];
        $duplicates = 0;

        foreach ($lines as $index => $line) {
            // Drops the "'" our exports put before =, +, - and @ (Csv::cell), so an exported file imports back as-is.
            $cells = array_map(fn (?string $cell) => preg_replace("/^'(?=[=+\-@])/", '', trim((string) $cell)), str_getcsv($line, $delimiter));
            if (implode('', $cells) === '' || ($index === 0 && in_array(mb_strtolower($cells[0]), ['nama', 'name'], true))) {
                continue;
            }

            $row = ['name' => $cells[0], 'phone' => ($cells[1] ?? '') ?: null, 'notes' => ($cells[2] ?? '') ?: null];
            $validator = Validator::make($row, SaveGuestRequest::fieldRules());
            if ($validator->fails()) {
                $errors[] = ['row' => $index + 1, 'message' => $validator->errors()->first()];
                continue;
            }

            if ($existing->has($key = mb_strtolower($row['name']))) {
                $duplicates++;
                continue;
            }

            $existing[$key] = true;
            $rows[] = $row;
        }

        if (count($rows) > 2000) {
            throw ValidationException::withMessages(['file' => __('Import at most 2000 guests at a time.')]);
        }

        DB::transaction(fn () => collect($rows)->each(fn (array $row) => Guest::create($row)));

        return response()->json([
            'data' => ['created' => count($rows), 'duplicates' => $duplicates, 'errors' => $errors],
            'message' => 'ok',
        ]);
    }

    /**
     * Save the WhatsApp invitation text. Null/empty restores the default.
     */
    public function updateMessage(Request $request): JsonResponse
    {
        Gate::authorize('manage', Guest::class);

        $data = $request->validate(
            ['message' => ['nullable', 'string', 'max:2000', 'regex:/\{link\}/']],
            ['message.regex' => __('The message must contain {link}, or guests get no invitation link.')],
        );

        $setting = Setting::current();
        $setting->guest_message = $data['message'] ?? null;
        $setting->save();

        return response()->json([
            'data' => ['message_template' => $setting->guest_message ?: Guest::DEFAULT_MESSAGE],
            'message' => 'ok',
        ]);
    }

    public function store(SaveGuestRequest $request): JsonResponse
    {
        $guest = Guest::create($request->validated());

        return response()->json([
            'data' => new GuestResource($guest),
            'message' => 'ok',
        ], 201);
    }

    public function update(SaveGuestRequest $request, Guest $guest): JsonResponse
    {
        $guest->update($request->validated());

        return response()->json([
            'data' => new GuestResource($guest),
            'message' => 'ok',
        ]);
    }

    public function destroy(Guest $guest): JsonResponse
    {
        Gate::authorize('manage', Guest::class);

        $guest->delete();

        return response()->json([
            'data' => null,
            'message' => 'ok',
        ]);
    }
}
