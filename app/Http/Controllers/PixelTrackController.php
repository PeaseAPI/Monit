<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\PixelTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

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
                $website = $fetchWebsite();
                Cache::put($cacheKey, $website ?? self::CACHE_NOT_FOUND, $cacheTtl);
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
            $reason = 'website_not_found';
        }

        // 简单跳过原因计数（供 Admin 观测，不记录 PII）
        if ($reason !== 'untracked') {
            logger()->channel('single')->debug('pixel skipped: '.$reason);
        }

        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type')
            ->header('Cache-Control', 'no-store');
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
