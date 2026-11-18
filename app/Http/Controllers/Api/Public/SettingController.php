<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/**
 * Unauthenticated, read-only — reuses the existing SettingResource as-is:
 * site_identity/contact/social/seo were already free of admin-only fields
 * (no user info, no internal bookkeeping), so a separate public resource
 * would just duplicate it for no benefit.
 */
class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => new SettingResource(Setting::current()),
        ]);
    }
}
