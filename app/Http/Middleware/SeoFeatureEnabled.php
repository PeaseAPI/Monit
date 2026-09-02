<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEO 功能开关（后台设置页 seo 组，别名 seo.feature:audits|tools）
 * - seo.audits_is_enabled：SEO 审计（分析/目录/复审调度）
 * - seo.tools_is_enabled：SEO 工具中心
 * 未配置默认开启；关闭后 403（与 SeoGuestAccess 风格一致）
 */
class SeoFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // 设置存储为 'true'/'false' 字符串（saveSettings 约定）：
        // (bool)'false' 为 true，须用 filter_var 归一化
        $enabled = Settings::get("seo.{$feature}_is_enabled", true);

        if (! filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
            abort(403, __('seo.feature_disabled'));
        }

        return $next($request);
    }
}
