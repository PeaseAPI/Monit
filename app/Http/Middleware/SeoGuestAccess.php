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
        if (auth()->check()) {
            return $next($request);
        }

        $enabled = Settings::get('seo.tools_guest_access');

        if ($enabled !== true && $enabled !== 'true' && $enabled !== '1') {
            abort(403, __('seo.guest_disabled'));
        }

        return $next($request);
    }
}
