<?php

namespace App\Http\Controllers;

use App\Models\Heatmap;
use App\Models\HeatmapSnapshotClick;
use App\Models\HeatmapSnapshotScroll;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 用户中心 - 热图管理
 * 规格书 §6.2.2：Heatmaps / Heatmap / HeatmapsAjax
 */
class HeatmapController extends Controller
{
    public function index(Request $request, Website $website)
    {
        $heatmaps = $website->heatmaps()->orderByDesc('heatmap_id')->get();

        return view('stats.heatmaps.index', compact('website', 'heatmaps'));
    }

    public function create(Request $request, Website $website)
    {
        return view('stats.heatmaps.create', compact('website'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'website_id' => ['required', 'integer'],
            'path' => ['required', 'string', 'max:2048'],
            'name' => ['required', 'string', 'max:256'],
            'is_enabled' => ['boolean'],
        ]);

        $website = $request->user()->websites()->findOrFail($validated['website_id']);

        Heatmap::create([
            ...$validated,
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        return redirect()->route('stats.heatmaps', ['website' => $website->website_id])
            ->with('success', __('msg.heatmap_created'));
    }

    public function show(Request $request, Website $website, int $heatmapId)
    {
        $heatmap = $website->heatmaps()->findOrFail($heatmapId);

        $clicks = HeatmapSnapshotClick::where('website_id', $website->website_id)
            ->whereNotNull('snapshot_id')
            ->limit(500)
            ->get();

        $scrolls = HeatmapSnapshotScroll::where('website_id', $website->website_id)
            ->orderByDesc('max_scroll')
            ->get()
            ->groupBy('max_scroll')
            ->map(fn ($group) => $group->count());

        return view('stats.heatmaps.show', compact('website', 'heatmap', 'clicks', 'scrolls'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'heatmap_id' => ['required', 'exists:websites_heatmaps,heatmap_id'],
            'path' => ['required', 'string', 'max:2048'],
            'name' => ['required', 'string', 'max:256'],
            'is_enabled' => ['boolean'],
        ]);

        $heatmap = Heatmap::find($validated['heatmap_id']);
        $website = Website::where('website_id', $heatmap->website_id)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();
        $websiteId = $website->website_id;
        $heatmap->update($validated);

        return redirect()->route('stats.heatmaps', ['website' => $websiteId])
            ->with('success', __('msg.heatmap_updated'));
    }

    public function destroy(Request $request, int $heatmapId): RedirectResponse
    {
        $heatmap = Heatmap::findOrFail($heatmapId);
        $website = Website::where('website_id', $heatmap->website_id)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();
        $websiteId = $website->website_id;
        $heatmap->delete();

        return redirect()->route('stats.heatmaps', ['website' => $websiteId])
            ->with('success', __('msg.heatmap_deleted'));
    }

    /**
     * 热图AJAX数据（规格书 §6.2.2：/heatmaps-ajax）
     */
    public function ajax(Request $request, Website $website, int $heatmapId)
    {
        $heatmap = Heatmap::where('website_id', $website->website_id)
            ->findOrFail($heatmapId);

        $clicks = HeatmapSnapshotClick::where('heatmap_id', $heatmapId)
            ->selectRaw('x, y, count(*) as count')
            ->groupBy('x', 'y')
            ->get();

        $scrolls = HeatmapSnapshotScroll::where('heatmap_id', $heatmapId)
            ->selectRaw('depth, count(*) as count')
            ->groupBy('depth')
            ->get();

        return response()->json([
            'heatmap' => $heatmap,
            'clicks' => $clicks,
            'scrolls' => $scrolls,
        ]);
    }
}
