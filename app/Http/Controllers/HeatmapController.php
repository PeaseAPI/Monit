<?php

namespace App\Http\Controllers;

use App\Models\Heatmap;
use App\Models\HeatmapSnapshotClick;
use App\Models\HeatmapSnapshotScroll;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 用户中心 - 热图管理
 * 规格书 §6.2.2：Heatmaps / Heatmap / HeatmapsAjax
 */
class HeatmapController extends Controller
{
    /**
     * @return View
     */
    public function index(Request $request, Website $website)
    {
        $heatmaps = $website->heatmaps()->orderByDesc('heatmap_id')->get();

        return view('stats.heatmaps.index', compact('website', 'heatmaps'));
    }

    /**
     * @return View
     */
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

        $website = $request->user()->websites()->where('website_id', (int) $validated['website_id'])->firstOrFail();

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

    /**
     * @return View
     */
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

        // 设备类型（前端设备选择器 & 快照 AJAX 请求参数）
        $device = $request->query('device', 'desktop');
        if (! in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $device = 'desktop';
        }

        // Check if a renderable DOM snapshot exists (needs snapshot_id and rrweb events in data)
        // 注意：必须用原生 SQL 读取 LONGBLOB data 列，Eloquent 的 PDO 绑定会破坏二进制数据
        $hasSnapshot = false;
        $hasLegacySnapshot = false;
        $snapshotIds = array_filter([
            $heatmap->snapshot_id_desktop,
            $heatmap->snapshot_id_tablet,
            $heatmap->snapshot_id_mobile,
        ]);
        foreach ($snapshotIds as $snapshotId) {
            $row = DB::selectOne(
                'SELECT data FROM heatmaps_snapshots WHERE snapshot_id = ?',
                [$snapshotId],
            );
            if (! $row || ! $row->data || strlen($row->data) <= 10) {
                continue;
            }
            // 解压检查数据格式：rrweb 事件格式含 events 键；旧格式只有 dom/viewport
            $decompressed = @gzdecode($row->data);
            if ($decompressed === false) {
                continue;
            }
            $parsed = json_decode($decompressed, true);
            if (is_array($parsed) && isset($parsed['events']) && is_array($parsed['events']) && count($parsed['events']) > 0) {
                // 包含 rrweb 事件 → 可用 rrweb-player 渲染网页截图
                // （仅当前设备计为可渲染；其他设备的截图切换设备 tab 后由 AJAX 加载）
                if ($snapshotId === $heatmap->{"snapshot_id_{$device}"}) {
                    $hasSnapshot = true;
                }
            } elseif (is_array($parsed) && (isset($parsed['dom']) || isset($parsed['viewport']))) {
                // 旧格式数据（仅有 dom 文本摘要），无法用 rrweb-player 渲染
                // 任一设备存在旧格式即提示（老数据通常只录了单一设备，切 tab 也应可见提示）
                $hasLegacySnapshot = true;
            }
        }

        return view('stats.heatmaps.show', compact('website', 'heatmap', 'clicks', 'scrolls', 'device', 'hasSnapshot', 'hasLegacySnapshot'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'heatmap_id' => ['required', 'exists:websites_heatmaps,heatmap_id'],
            'path' => ['required', 'string', 'max:2048'],
            'name' => ['required', 'string', 'max:256'],
            'is_enabled' => ['boolean'],
        ]);

        $heatmap = Heatmap::query()->where('heatmap_id', (int) $validated['heatmap_id'])->firstOrFail();
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
     *
     * @return JsonResponse
     */
    public function ajax(Request $request, Website $website, int $heatmapId)
    {
        $heatmap = Heatmap::where('website_id', $website->website_id)
            ->findOrFail($heatmapId);

        $snapshotIds = $heatmap->snapshotIds();

        $clicks = HeatmapSnapshotClick::where('website_id', $website->website_id)
            ->whereIn('snapshot_id', $snapshotIds)
            ->selectRaw('x_normalized, y_normalized, SUM(count) as count')
            ->groupBy('x_normalized', 'y_normalized')
            ->get();

        $scrolls = HeatmapSnapshotScroll::where('website_id', $website->website_id)
            ->whereIn('snapshot_id', $snapshotIds)
            ->selectRaw('max_scroll, count(*) as count')
            ->groupBy('max_scroll')
            ->get();

        return response()->json([
            'heatmap' => $heatmap,
            'clicks' => $clicks,
            'scrolls' => $scrolls,
        ]);
    }

    /**
     * 返回热图 DOM 快照 JSON（供 rrweb-player 渲染网页截图）
     * 数据是 gzencode 压缩的，解压后直接输出 JSON
     *
     * @return Response|JsonResponse
     */
    public function snapshot(Request $request, Website $website, int $heatmapId)
    {
        $heatmap = Heatmap::where('website_id', $website->website_id)
            ->findOrFail($heatmapId);

        $device = $request->query('device', 'desktop');
        if (! in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $device = 'desktop';
        }

        // 优先取请求的设备，无数据则回退到其他设备
        $devices = [$device];
        foreach (['desktop', 'tablet', 'mobile'] as $d) {
            if ($d !== $device) {
                $devices[] = $d;
            }
        }

        foreach ($devices as $d) {
            $snapshotId = $heatmap->{"snapshot_id_{$d}"};
            if (! $snapshotId) {
                continue;
            }

            // 用原生查询读取 BLOB，避免 Eloquent 对二进制数据的编码问题
            $row = DB::selectOne(
                'SELECT `data` FROM `heatmaps_snapshots` WHERE `snapshot_id` = ?',
                [$snapshotId],
            );

            if ($row && $row->data) {
                $decompressed = @gzdecode($row->data);
                if ($decompressed !== false) {
                    return response($decompressed, 200, ['Content-Type' => 'application/json']);
                }
            }
        }

        return response()->json([]);
    }
}
