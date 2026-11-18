<?php

namespace App\Http\Controllers\Api;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pages\DestroyPageRequest;
use App\Http\Requests\Pages\IndexPageRequest;
use App\Http\Requests\Pages\ShowPageRequest;
use App\Http\Requests\Pages\StorePageRequest;
use App\Http\Requests\Pages\UpdatePageHomeRequest;
use App\Http\Requests\Pages\UpdatePageRequest;
use App\Http\Requests\Pages\UpdatePageStatusRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageController extends Controller
{
    public function __construct(
        protected PageService $pageService
    ) {}

    /**
     * List pages — paginated, searchable by title, filterable by status.
     */
    public function index(IndexPageRequest $request): AnonymousResourceCollection
    {
        $pages = Page::query()
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('updated_at')
            ->paginate($request->integer('per_page', 15));

        return PageResource::collection($pages);
    }

    /**
     * Create a page. Always starts as draft — see PageService::create.
     */
    public function store(StorePageRequest $request): JsonResponse
    {
        $page = $this->pageService->create($request->validated(), $request->user()->id);

        return response()->json([
            'data'    => new PageResource($page),
            'message' => 'ok',
        ], 201);
    }

    /**
     * Show a single page.
     */
    public function show(ShowPageRequest $request, Page $page): JsonResponse
    {
        return response()->json([
            'data'    => new PageResource($page),
            'message' => 'ok',
        ]);
    }

    /**
     * Update a page's title/slug/content/meta fields.
     */
    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $page = $this->pageService->update($page, $request->validated(), $request->user()->id);

        return response()->json([
            'data'    => new PageResource($page),
            'message' => 'ok',
        ]);
    }

    /**
     * Publish or unpublish a page — admin only (see PagePolicy::publish).
     */
    public function updateStatus(UpdatePageStatusRequest $request, Page $page): JsonResponse
    {
        $status = PageStatus::from($request->validated('status'));
        $page = $this->pageService->setStatus($page, $status, $request->user()->id);

        return response()->json([
            'data'    => new PageResource($page),
            'message' => 'ok',
        ]);
    }

    /**
     * Designate or un-designate a page as the site homepage — admin only
     * (see PagePolicy::setHome). Only a published page may be made home.
     */
    public function updateHome(UpdatePageHomeRequest $request, Page $page): JsonResponse
    {
        $page = $this->pageService->setHome($page, $request->boolean('is_home'), $request->user()->id);

        return response()->json([
            'data'    => new PageResource($page),
            'message' => 'ok',
        ]);
    }

    /**
     * Delete a page — admin only.
     */
    public function destroy(DestroyPageRequest $request, Page $page): JsonResponse
    {
        $this->pageService->delete($page);

        return response()->json([
            'data'    => null,
            'message' => 'ok',
        ]);
    }
}
