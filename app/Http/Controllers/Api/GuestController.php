<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guests\SaveGuestRequest;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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

        return GuestResource::collection($guests);
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
