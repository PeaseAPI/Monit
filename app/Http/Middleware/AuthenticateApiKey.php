<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        if (Settings::get('main.api_is_enabled') === 'false') {
            return response()->json(['error' => 'API is disabled'], 403);
        }

        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json(['error' => 'Unauthorized — Bearer token required'], 401);
        }

        // 失败尝试限流（安全审计周期 #7）：防 Bearer 爆破。仅失败计数、
        // 成功即清零——不影响正常 API 流量（路由级 throttle 会误伤高频轮询）
        $failures = 'api-key-failures.'.$request->ip();
        if (RateLimiter::tooManyAttempts($failures, 10)) {
            return response()->json(['error' => 'Too many invalid API key attempts'], 429);
        }

        $user = User::where('api_key_lookup', hash('sha256', $bearer))->first();

        // 解密值恒时比较确认（第十三轮）：lookup 命中后再验明文，防哈希碰撞/
        // 遗留明文行双轨歧义；恒时比较防解密值比对侧信道
        if (! $user || ! hash_equals((string) $user->api_key, $bearer)) {
            $user = null;
        }

        if (! $user) {
            RateLimiter::hit($failures, 900);

            return response()->json(['error' => 'Invalid API key'], 401);
        }

        RateLimiter::clear($failures);

        if (isset($user->status) && $user->status !== 1) {
            return response()->json(['error' => 'Account disabled'], 403);
        }

        // 以该用户身份登录，使 auth() 可用
        auth('web')->login($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
