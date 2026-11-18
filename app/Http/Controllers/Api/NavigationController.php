<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Navigation\DestroyNavigationItemRequest;
use App\Http\Requests\Navigation\IndexNavigationItemRequest;
use App\Http\Requests\Navigation\StoreNavigationItemRequest;
use App\Http\Requests\Navigation\UpdateNavigationItemRequest;
use App\Http\Requests\Navigation\UpdateNavigationItemVisibilityRequest;
use App\Http\Resources\NavigationItemResource;
use App\Models\NavigationItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NavigationController extends Controller
{
    /**
     * List all navigation items, ordered. Not paginated — a nav menu is
     * expected to stay small, and the frontend renders it as one list.
     */
    public function index(IndexNavigationItemRequest $request): AnonymousResourceCollection
    {
        $items = NavigationItem::query()->orderBy('order')->get();

        return NavigationItemResource::collection($items);
    }

    /**
     * Create a navigation item. New items are appended to the end of the
     * order unless an explicit `order` is given.
     */
    public function store(StoreNavigationItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['order'] ??= ((int) NavigationItem::max('order')) + 1;

        // `visible` isn't fillable (see NavigationItem), and the DB-level
        // default wouldn't be reflected on this in-memory instance without
        // an extra query — set it explicitly instead.
        $item = new NavigationItem($data);
        $item->visible = true;
        $item->save();

        return response()->json([
            'data'    => new NavigationItemResource($item),
            'message' => 'ok',
        ], 201);
    }

    /**
     * Update a navigation item's label, url, and/or order.
     */
    public function update(UpdateNavigationItemRequest $request, NavigationItem $item): JsonResponse
    {
        $item->update($request->validated());

        return response()->json([
            'data'    => new NavigationItemResource($item),
            'message' => 'ok',
        ]);
    }

    /**
     * Show or hide a navigation item.
     */
    public function updateVisibility(UpdateNavigationItemVisibilityRequest $request, NavigationItem $item): JsonResponse
    {
        $item->visible = $request->boolean('visible');
        $item->save();

        return response()->json([
            'data'    => new NavigationItemResource($item),
            'message' => 'ok',
        ]);
    }

    /**
     * Delete a navigation item.
     */
    public function destroy(DestroyNavigationItemRequest $request, NavigationItem $item): JsonResponse
    {
        $item->delete();

        return response()->json([
            'data'    => null,
            'message' => 'ok',
        ]);
    }
}
