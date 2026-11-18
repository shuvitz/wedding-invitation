<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ShowSettingRequest;
use App\Http\Requests\Settings\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Show the (single) settings record.
     */
    public function show(ShowSettingRequest $request): JsonResponse
    {
        return response()->json([
            'data'    => new SettingResource(Setting::current()),
            'message' => 'ok',
        ]);
    }

    /**
     * Update the settings record. Each provided group is merged into the
     * existing group rather than replacing it wholesale, so a request
     * touching only `contact` doesn't wipe `social`/`seo`/etc.
     */
    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $setting = Setting::current();
        $data = $request->validated();

        foreach (['site_identity', 'contact', 'social', 'seo'] as $group) {
            if (array_key_exists($group, $data)) {
                $setting->{$group} = array_merge($setting->{$group} ?? [], $data[$group]);
            }
        }

        $setting->save();

        return response()->json([
            'data'    => new SettingResource($setting->fresh()),
            'message' => 'ok',
        ]);
    }
}
