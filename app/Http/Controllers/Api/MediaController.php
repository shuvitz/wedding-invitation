<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\DestroyMediaRequest;
use App\Http\Requests\Media\IndexMediaRequest;
use App\Http\Requests\Media\ShowMediaRequest;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    /**
     * List media — paginated, searchable by original filename, filterable
     * by MIME type.
     */
    public function index(IndexMediaRequest $request): AnonymousResourceCollection
    {
        $media = Media::query()
            ->when($request->filled('search'), fn ($query) => $query->where('original_name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('mime_type'), fn ($query) => $query->where('mime_type', $request->string('mime_type')))
            ->latest()
            ->paginate($request->integer('per_page', 24));

        return MediaResource::collection($media);
    }

    /**
     * Upload a new media item.
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $media = $this->mediaService->upload(
            $request->file('file'),
            $request->user()->id,
            $request->validated('alt_text')
        );

        return response()->json([
            'data'    => new MediaResource($media),
            'message' => 'ok',
        ], 201);
    }

    /**
     * Show a single media item.
     */
    public function show(ShowMediaRequest $request, Media $media): JsonResponse
    {
        return response()->json([
            'data'    => new MediaResource($media),
            'message' => 'ok',
        ]);
    }

    /**
     * Update a media item's alt text.
     */
    public function update(UpdateMediaRequest $request, Media $media): JsonResponse
    {
        $media = $this->mediaService->updateAltText($media, $request->validated('alt_text'));

        return response()->json([
            'data'    => new MediaResource($media),
            'message' => 'ok',
        ]);
    }

    /**
     * Delete a media item — blocked with a 422 if it's currently referenced.
     */
    public function destroy(DestroyMediaRequest $request, Media $media): JsonResponse
    {
        $this->mediaService->delete($media);

        return response()->json([
            'data'    => null,
            'message' => 'ok',
        ]);
    }
}
