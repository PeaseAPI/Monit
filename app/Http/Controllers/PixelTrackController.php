<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\PixelTracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Monit 像素采集端点
 * 依据规格书 §4.1：POST /pixel-track/{pixel_key}
 * - CORS 全开，SameSite=Strict，Cache-Control: no-store
 * - has_view=false（无视图输出，始终 204）
 */
class PixelTrackController extends Controller
{
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
        $website = $cacheTtl > 0
            ? \Illuminate\Support\Facades\Cache::remember(
                'pixel.website.'.$pixel_key,
                $cacheTtl,
                fn () => Website::where('pixel_key', $pixel_key)->with('user')->first(),
            )
            : Website::where('pixel_key', $pixel_key)->with('user')->first();

        if ($website) {
            $tracker->handle($website, $request);
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
