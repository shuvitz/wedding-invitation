<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\PublicNavigationItemResource;
use App\Models\NavigationItem;
use Illuminate\Http\JsonResponse;

class NavigationController extends Controller
{
    public function index(): JsonResponse
    {
        $items = NavigationItem::query()
            ->where('visible', true)
            ->orderBy('order')
            ->get();

        return response()->json([
            'data' => PublicNavigationItemResource::collection($items),
        ]);
    }
}
