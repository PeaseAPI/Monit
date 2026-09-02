<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEO 工具中心访客访问控制（seo.tools_guest_access 开关，融合方案 §7）
 */
class SeoGuestAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            return $next($request);
        }

        // 默认值 true 与 ProductionSeeder 初始态一致：DB 缺 key（旧库升级未重播种）
        // 时不再误判为关闭，避免访客侧 SEO 入口莫名 403
        $enabled = Settings::get('seo.tools_guest_access', true);

        if (! filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
            abort(403, __('seo.guest_disabled'));
        }

        return $next($request);
    }
}
