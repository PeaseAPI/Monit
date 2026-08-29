<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\EventChild;
use App\Models\Heatmap;
use App\Models\OutboundClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API v1 - 网站级数据资源：热图 / 事件子项 / 出站点击
 * 规格书 §8：/api/heatmaps、/api/events-children、/api/outbound-clicks
 */
class WebsiteDataController
{
    /* ---------------- 热图 ---------------- */

    public function heatmapsIndex(Request $request, int $website): JsonResponse
    {
        return response()->json($this->ownWebsite($request, $website)->heatmaps()->orderByDesc('heatmap_id')->get());
    }

    public function heatmapsStore(Request $request, int $website): JsonResponse
    {
        $website = $this->ownWebsite($request, $website);

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:512'],
            'name' => ['required', 'string', 'max:256'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $heatmap = $website->heatmaps()->create([
            ...$validated,
            'is_enabled' => $validated['is_enabled'] ?? true,
            'datetime' => now(),
        ]);

        return response()->json(['message' => __('msg.heatmap_created'), 'heatmap' => $heatmap], 201);
    }

    public function heatmapsUpdate(Request $request, int $website, int $heatmap): JsonResponse
    {
        $heatmap = Heatmap::where('website_id', $this->ownWebsite($request, $website)->website_id)
            ->where('heatmap_id', $heatmap)->firstOrFail();

        $validated = $request->validate([
            'path' => ['sometimes', 'string', 'max:512'],
            'name' => ['sometimes', 'string', 'max:256'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        $heatmap->update($validated);

        return response()->json(['message' => __('msg.heatmap_updated'), 'heatmap' => $heatmap]);
    }

    public function heatmapsDestroy(Request $request, int $website, int $heatmap): JsonResponse
    {
        Heatmap::where('website_id', $this->ownWebsite($request, $website)->website_id)
            ->where('heatmap_id', $heatmap)->firstOrFail()->delete();

        return response()->json(['message' => __('msg.heatmap_deleted')]);
    }

    /* ---------------- 事件子项 ---------------- */

    public function eventChildrenIndex(Request $request, int $website): JsonResponse
    {
        $query = EventChild::whereIn('event_id', function ($q) use ($website) {
            $q->select('event_id')->from('sessions_events')->where('website_id', $website);
        });

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        return response()->json($query->orderByDesc('event_child_id')->limit(200)->get());
    }

    /* ---------------- 出站点击 ---------------- */

    public function outboundClicksIndex(Request $request, int $website): JsonResponse
    {
        $query = OutboundClick::where('website_id', $this->ownWebsite($request, $website)->website_id);

        if ($host = $request->query('host')) {
            $query->where('host', $host);
        }

        return response()->json($query->orderByDesc('outbound_click_id')->limit(200)->get());
    }

    /* ---------------- 辅助 ---------------- */

    protected function ownWebsite(Request $request, int $websiteId)
    {
        return $request->user()->websites()->where('websites.website_id', $websiteId)->firstOrFail();
    }
}
