<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 维护模式中间件（规格书 §6.1：/maintenance）
 * settings main.maintenance_is_enabled 开启时，非管理员跳转维护页
 */
class CheckMaintenance
{
    protected array $except = [
        'maintenance',
        'admin',
        'login',
        'logout',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Settings::get('main.maintenance_is_enabled') !== 'true') {
            return $next($request);
        }

        if (auth()->check() && auth()->user()->isAdmin()) {
            return $next($request);
        }

        $path = $request->path() === '/' ? '' : $request->path();

        foreach ($this->except as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $next($request);
            }
        }

        // 像素采集与 API 不受维护模式影响
        if ($request->is('pixel-track/*') || $request->is('api/*') || $request->is('webhooks/*')) {
            return $next($request);
        }

        return redirect()->route('maintenance');
    }
}
