<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API v1 - 网站管理接口
 * 规格书 §4.2：Website API
 */
class WebsiteController
{
    public function index(Request $request): JsonResponse
    {
        $websites = $request->user()->websites()->orderByDesc('website_id')->get();

        return response()->json($websites);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'host' => ['required', 'string', 'max:256'],
            'timezone' => ['required', 'timezone'],
            'tracking_type' => ['sometimes', 'in:advanced,lightweight'],
        ]);

        $pixelKey = '';
        do {
            $pixelKey = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(32));
        } while (Website::where('pixel_key', $pixelKey)->exists());

        $website = Website::create([
            ...$validated,
            'user_id' => $request->user()->user_id,
            'pixel_key' => $pixelKey,
            'scheme' => 'https',
            'tracking_type' => $validated['tracking_type'] ?? 'advanced',
            'bot_exclusion_is_enabled' => true,
        ]);

        return response()->json([
                        'message' => __('msg.website_created_api'),
            'website' => $website,
        ], 201);
    }

        public function show(Request $request, Website $website): JsonResponse
    {
        return response()->json($website);
    }

    public function update(Request $request, Website $website): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:256'],
            'host' => ['sometimes', 'string', 'max:256'],
            'timezone' => ['sometimes', 'timezone'],
            'tracking_type' => ['sometimes', 'in:advanced,lightweight'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        $website->update($validated);

                return response()->json(['message' => __('msg.website_updated_api'), 'website' => $website]);
    }

    public function destroy(Request $request, Website $website): JsonResponse
    {
        $website->delete();

                return response()->json(['message' => __('msg.website_deleted_api')]);
    }
}