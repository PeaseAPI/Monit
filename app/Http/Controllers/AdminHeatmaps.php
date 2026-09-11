<?php

namespace App\Http\Controllers;

use App\Models\Heatmap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 管理后台 - 平台级热图管理
 * 规格书 §6.3.5 / 附B：AdminHeatmaps
 */
class AdminHeatmaps extends Controller
{
    /**
     * @return View
     */
    public function index(Request $request)
    {
        $heatmaps = Heatmap::with('website')
            ->withCount('snapshots')
            ->when($request->input('search'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderByDesc('heatmap_id')
            ->paginate(25);

        return view('admin.heatmaps.index', compact('heatmaps'))->with('adminNav', 'heatmaps');
    }

    public function destroy(int $heatmapId): RedirectResponse
    {
        $heatmap = Heatmap::with('snapshots')->findOrFail($heatmapId);

        // 快照与主记录同事务删除，避免留下孤儿快照
        DB::transaction(function () use ($heatmap): void {
            // 级联删除快照与点击/滚动数据
            foreach ($heatmap->snapshots as $snapshot) {
                $snapshot->delete();
            }
            $heatmap->delete();
        });

        return redirect()->route('admin.heatmaps.index')
            ->with('success', __('msg.heatmap_deleted'));
    }
}
