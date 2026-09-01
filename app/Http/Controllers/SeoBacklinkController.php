<?php

namespace App\Http\Controllers;

use App\Models\SeoBacklink;
use App\Models\Website;
use App\Services\Seo\BacklinkChecker;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * 反链分析控制器（SEO模块融合方案 §8 扩展）
 * 台账 + 活性重验（BacklinkChecker 抓源页匹配目标站链接，零外部依赖）
 */
class SeoBacklinkController extends Controller
{
    public function index(Request $request): View
    {
        $query = SeoBacklink::with('website')
            ->where('user_id', $request->user()->user_id)
            ->orderByDesc('seo_backlink_id');

        $links = (clone $query)->paginate(20)->withQueryString();

        $all = (clone $query)->get();
        $active = $all->where('status', 'active');

        $summary = [
            'total' => $all->count(),
            'referring_domains' => $all->unique('source_host')->count(),
            'active' => $active->count(),
            'dofollow' => $all->where('rel', 'dofollow')->count(),
        ];

        return view('seo.backlinks', [
            'links' => $links,
            'summary' => $summary,
            'websites' => Website::where('user_id', $request->user()->user_id)->orderBy('host')->get(['website_id', 'host']),
        ]);
    }

    /**
     * 添加反链（手动台账 / API 发现结果导入）
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_url' => 'required|url|max:2048',
            'website_id' => 'nullable|integer|exists:websites,website_id',
            'target_url' => 'nullable|url|max:2048',
            'anchor_text' => 'nullable|string|max:512',
            'rel' => 'nullable|in:dofollow,nofollow,unknown',
            'dr' => 'nullable|integer|min:0|max:100',
        ]);

        $websiteId = null;

        if (! empty($validated['website_id'])) {
            $websiteId = Website::where('user_id', $request->user()->user_id)
                ->where('website_id', (int) $validated['website_id'])
                ->value('website_id');
        }

        $exists = SeoBacklink::where('user_id', $request->user()->user_id)
            ->where('url_hash', SeoBacklink::hashOf($validated['source_url'], $validated['target_url'] ?? null))
            ->exists();

        if ($exists) {
            return back()->withErrors(['source_url' => __('seo.backlink_exists')]);
        }

        SeoBacklink::create([
            'user_id' => $request->user()->user_id,
            'website_id' => $websiteId,
            'source_url' => $validated['source_url'],
            'source_host' => SeoBacklink::normalizeHost($validated['source_url']),
            'target_url' => $validated['target_url'] ?? null,
            'url_hash' => SeoBacklink::hashOf($validated['source_url'], $validated['target_url'] ?? null),
            'anchor_text' => $validated['anchor_text'] ?? null,
            'rel' => $validated['rel'] ?? 'unknown',
            'dr' => $validated['dr'] ?? null,
            'status' => 'pending',
            'first_seen_at' => now(),
        ]);

        return back()->with('success', __('seo.backlink_added'));
    }

    /**
     * 立即重验单条反链
     */
    public function verify(Request $request, SeoBacklink $backlink, BacklinkChecker $checker)
    {
        $this->authorizeOwn($request, $backlink);

        try {
            $status = $checker->verify($backlink);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['source_url' => __('seo.backlink_verify_failed')]);
        }

        return back()->with('success', __('seo.backlink_status_'.$status));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = SeoBacklink::where('user_id', $request->user()->user_id)->orderByDesc('seo_backlink_id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBFsource_url,source_host,target_url,anchor,rel,status,dr,first_seen,last_checked\n");

            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->source_url, $row->source_host, $row->target_url, $row->anchor_text,
                        $row->rel, $row->status, $row->dr,
                        $row->first_seen_at?->format('Y-m-d'), $row->last_checked_at?->format('Y-m-d'),
                    ]);
                }
            });

            fclose($out);
        }, 'seo-backlinks-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function destroy(Request $request, SeoBacklink $backlink)
    {
        $this->authorizeOwn($request, $backlink);

        $backlink->delete();

        return back()->with('success', __('seo.backlink_deleted'));
    }

    protected function authorizeOwn(Request $request, SeoBacklink $backlink): void
    {
        if ((int) $backlink->user_id !== (int) $request->user()->user_id && ! $request->user()->isAdmin()) {
            abort(403);
        }
    }
}
