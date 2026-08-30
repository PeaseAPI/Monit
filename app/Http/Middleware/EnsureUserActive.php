<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 封禁用户实时登出守卫
 * 规格书 §2 权限守卫：user 级 `status != 1` 立即登出（登录入口校验之外的会话级守卫，
 * 管理员封禁用户后其现有会话在下一个请求即被终止）
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        // web 组无全局 Authenticate，需主动经 session guard 解析当前用户
        $user = auth()->guard('web')->user();

        if ($user !== null && (int) $user->status !== 1) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', __('msg.account_banned'));
        }

        return $next($request);
    }
}
