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

        // datetime 列 NOT NULL 无默认值（模型 $timestamps=false），必须显式赋值，否则 SQL 报错 500
        Heatmap::create([
            'website_id' => $validated['website_id'],
            'path' => $validated['path'],
            'name' => $validated['name'],
            'is_enabled' => $request->boolean('is_enabled', true),
            'datetime' => now(),
        ]);

        return redirect()->route('stats.heatmaps', ['website' => $website->website_id])
            ->with('success', __('msg.heatmap_created'));
    }

    public function show(Request $request, Website $website, int $heatmapId)
    {
        $heatmap = $website->heatmaps()->findOrFail($heatmapId);

        $snapshotIds = $heatmap->snapshotIds();

        $clicks = HeatmapSnapshotClick::where('website_id', $website->website_id)
            ->whereIn('snapshot_id', $snapshotIds)
            ->limit(500)
            ->get();

        $scrolls = HeatmapSnapshotScroll::where('website_id', $website->website_id)
            ->whereIn('snapshot_id', $snapshotIds)
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

        // heatmap_id 不在模型 fillable 中（主键），混入 update 会触发 MassAssignmentException
        $heatmap->update([
            'path' => $validated['path'],
            'name' => $validated['name'],
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

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
     * clicks/scrolls 表按 snapshot_id 关联（无 heatmap_id 列）；坐标列为 x_normalized/y_normalized，滚动列为 max_scroll
     */
    public function ajax(Request $request, Website $website, int $heatmapId)
    {
        $heatmap = Heatmap::where('website_id', $website->website_id)
            ->findOrFail($heatmapId);

        $snapshotIds = $heatmap->snapshotIds();

        $clicks = HeatmapSnapshotClick::whereIn('snapshot_id', $snapshotIds)
            ->selectRaw('x_normalized, y_normalized, SUM(count) as count')
            ->groupBy('x_normalized', 'y_normalized')
            ->get();

        $scrolls = HeatmapSnapshotScroll::whereIn('snapshot_id', $snapshotIds)
            ->selectRaw('max_scroll, count(*) as count')
            ->groupBy('max_scroll')
            ->get();

        return response()->json([
            'heatmap' => $heatmap,
            'clicks' => $clicks,
            'scrolls' => $scrolls,
        ]);
    }
}
