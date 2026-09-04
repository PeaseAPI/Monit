<?php

namespace App\Http\Controllers;

use App\Models\Heatmap;
use App\Models\Website;
use App\Services\PixelTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Monit 像素采集端点
 * 依据规格书 §4.1：POST /pixel-track/{pixel_key}
 * - CORS 全开，SameSite=Strict，Cache-Control: no-store
 * - has_view=false（无视图输出，始终 204）
 */
class PixelTrackController extends Controller
{
    /** not-found 哨兵：缓存「pixel_key 无对应站点」结论（语义对齐 Cache::remember 的 null 缓存，防乱扫打穿 DB） */
    private const CACHE_NOT_FOUND = 'pixel.website.not_found';

    public function __invoke(Request $request, string $pixel_key, PixelTracker $tracker): Response
    {
        // 热图自动检测：GET ?action=heatmap_check&path=/some/page
        if ($request->isMethod('GET') && $request->query('action') === 'heatmap_check') {
            return $this->heatmapCheck($pixel_key, $request);
        }

        // 静默标记：无论处理/跳过均返回 204（不向客户端泄露信息）
        $reason = 'untracked';

        $tracker->onSkip(function (string $r) use (&$reason): void {
            $reason = $r;
        });

        // M23 性能优化：pixel_key → Website 查询走缓存（默认 60s，TTL 由 config/monit.php 控制），
        // 高频采集下将每次请求的 DB 查询降为缓存命中；写入侧 Website::saved 钩子主动失效。
        $cacheTtl = (int) config('monit.pixel.website_cache_ttl', 60);
        $cacheKey = 'pixel.website.'.$pixel_key;
        $fetchWebsite = fn (): ?Website => Website::where('pixel_key', $pixel_key)->with('user')->first();

        if ($cacheTtl <= 0) {
            $website = $fetchWebsite();
        } else {
            $cached = Cache::get($cacheKey);

            if ($cached instanceof Website) {
                $website = $cached;
            } elseif ($cached === self::CACHE_NOT_FOUND) {
                $website = null;
            } else {
                // 未命中 或 坏缓存（database 驱动 value 列截断产生的 __PHP_Incomplete_Class 等）：
                // 回源重建并覆盖坏条目——直接使用坏对象会触发 TypeError 逐请求上报
                //（关联：PixelTracker::handle 强类型 Website 参数；生产 cache.value 需 mediumtext）
                //
                // 负向限流：每 IP 未命中回源次数限流（config: website_miss_rate_limit / 分钟）。
                // 命中缓存的正常流量零开销；随机 pixel_key 扫描因每请求必 miss 被快速熔断，
                // 避免攻击者持续回源 DB / 向缓存灌入垃圾条目。超限后静默按无站点处理。
                $missLimit = (int) config('monit.pixel.website_miss_rate_limit', 60);
                $missKey = 'pixel.miss:'.$request->ip();

                if ($missLimit > 0 && RateLimiter::tooManyAttempts($missKey, $missLimit)) {
                    $website = null;
                    $reason = 'miss_throttled';
                } else {
                    if ($missLimit > 0) {
                        RateLimiter::hit($missKey, 60);
                    }
                    $website = $fetchWebsite();
                    Cache::put($cacheKey, $website ?? self::CACHE_NOT_FOUND, $cacheTtl);
                }
            }
        }

        if ($website) {
            try {
                $tracker->handle($website, $request);
            } catch (\Throwable $e) {
                // 采集端点永不 500：异常上报后仍按 204 静默返回（不向客户端泄露信息，
                // 也不打断被统计页面的加载）；关联 bug：外部域上报偶发 500 + CORS 报错
                report($e);
                $reason = 'exception';
            }
        } else {
            // 保留 miss_throttled 标记（区别于真正的无站点，便于观测熔断是否生效）
            $reason = $reason === 'miss_throttled' ? $reason : 'website_not_found';
        }

        // 简单跳过原因计数（供 Admin 观测，不记录 PII）
        if ($reason !== 'untracked') {
            Log::channel('single')->debug('pixel skipped: '.$reason);
        }

        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type')
            ->header('Cache-Control', 'no-store');
    }

    /**
     * 热图自动检测：给定 pixel_key + path，返回匹配的 heatmap_id
     * 客户端 SDK 在页面加载时自动调用，无需站点手动配置 data-heatmap-id
     */
    protected function heatmapCheck(string $pixel_key, Request $request): Response
    {
        $cacheTtl = (int) config('monit.pixel.website_cache_ttl', 60);
        $cacheKey = 'pixel.website.'.$pixel_key;
        $website = $cacheTtl > 0 ? Cache::get($cacheKey) : null;

        if (! $website instanceof Website) {
            $website = Website::where('pixel_key', $pixel_key)->first();
        }

        if (! $website || ! $website->is_enabled) {
            return response('{}', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Content-Type', 'application/json')
                ->header('Cache-Control', 'no-store');
        }

        $path = $request->query('path', '/');
        $heatmap = Heatmap::where('website_id', $website->website_id)
            ->where('is_enabled', true)
            ->where('path', $path)
            ->first();

        if (! $heatmap) {
            // 通配符匹配 /path/*
            $heatmap = Heatmap::where('website_id', $website->website_id)
                ->where('is_enabled', true)
                ->whereRaw('? LIKE CONCAT(REPLACE(path, "*", "%"))', [$path])
                ->first();
        }

                // 全局热图开关开启但无匹配 Heatmap → 自动创建（修复热图无数据根因）
        $globalHeatmapsEnabled = (bool) settings()->analytics->websites_heatmaps_is_enabled;
        if (! $heatmap && $globalHeatmapsEnabled) {
            $heatmap = Heatmap::create([
                'website_id' => $website->website_id,
                'user_id' => $website->user_id,
                'path' => $path,
                'name' => $path === '/' ? 'Homepage' : ltrim($path, '/'),
                'is_enabled' => true,
                'datetime' => now(),
            ]);
        }

        // 判断回放是否启用：全局开关 + 网站开关 + 套餐配额
        $replayEnabled = false;
        if ((bool) settings()->analytics->sessions_replays_is_enabled && $website->sessions_replays_is_enabled) {
            $replayLimit = $website->user?->getPlanSettings()['sessions_replays_limit'] ?? 0;
            $replayEnabled = ($replayLimit === -1) || ($replayLimit > 0 && $website->current_month_sessions_replays < $replayLimit);
        }

        $data = $heatmap
            ? json_encode(['heatmap_id' => $heatmap->heatmap_id, 'replay_enabled' => $replayEnabled])
            : json_encode(['replay_enabled' => $replayEnabled]);

        return response($data, 200)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * OPTIONS 预检
     */
    public function preflight(): Response
    {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type')
            ->header('Access-Control-Max-Age', '86400')
            ->header('Cache-Control', 'no-store');
    }
}
