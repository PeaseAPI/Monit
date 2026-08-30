<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Key 认证中间件
 * 解析 Authorization: Bearer <api_key> 头，校验用户 api_key 字段
 * 规格书 §12.2：通过 Bearer Token 鉴权
 *
 * main.api_is_enabled === 'false' 时 API 整体关闭（显式关闭语义：未设置默认可用）
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (\App\Support\Settings::get('main.api_is_enabled') === 'false') {
            return response()->json(['error' => 'API is disabled'], 403);
        }

        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json(['error' => 'Unauthorized — Bearer token required'], 401);
        }

        $user = User::where('api_key', $bearer)->first();

        if (! $user) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        if (isset($user->status) && $user->status !== 1) {
            return response()->json(['error' => 'Account disabled'], 403);
        }

        // 以该用户身份登录，使 auth() 可用
        auth('web')->login($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
