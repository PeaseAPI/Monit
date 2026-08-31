<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin 中间件 - 仅允许 type=1 的管理员访问
 * 规格书 §6.3：Admin 区域权限
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->type !== 1) {
            abort(403, __('msg.forbidden_admin'));
        }

        return $next($request);
    }
}
