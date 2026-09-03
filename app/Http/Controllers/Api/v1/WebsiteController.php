<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            $pixelKey = Str::lower(Str::random(32));
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
        $this->authorizeWebsite($website);

        return response()->json($website);
    }

    public function update(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

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
        $this->authorizeWebsite($website);

        $website->delete();

        return response()->json(['message' => __('msg.website_deleted_api')]);
    }

    /**
     * 所有权检查（安全审计周期 #15）：此前 show/update/destroy 无任何
     * 校验，任意 API Key 可读/改/删任意用户网站。与兄弟控制器
     * （ApiSessions/ApiVisitors 等）保持一致的 owner/admin 语义
     */
    protected function authorizeWebsite(Website $website): void
    {
        if ((int) $website->user_id !== (int) auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
