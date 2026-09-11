<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * SSO 单点登录控制器
 * 规格书 §6.1：/sso - 第三方系统对接登录
 *
 * 接受外部系统传来的 token/user_id/email 参数，验证后自动登录
 */
class SsoController extends Controller
{
    /**
     * SSO 登录入口
     *
     * 外部系统需在 admin settings 配置 SSO Secret Key
     * 请求参数：token (HMAC-SHA256 签名), user_id 或 email, timestamp
     */
    public function login(Request $request): RedirectResponse
    {
        $ssoSecret = Setting::where('key', 'main.sso_secret_key')->value('value');
        $ssoEnabled = Setting::where('key', 'main.sso_is_enabled')->value('value');

        if (! $ssoEnabled || $ssoEnabled !== 'true' || ! $ssoSecret) {
            return redirect()->route('login')->withErrors(['sso' => __('auth.sso_not_enabled')]);
        }

        $request->validate([
            'token' => 'required|string',
            'timestamp' => 'required|integer',
            'user_id' => 'nullable|integer',
            'email' => 'nullable|email',
        ]);

        // 验证时间戳（5分钟内有效）
        if (abs(time() - Typed::int($request->input('timestamp'))) > 300) {
            return redirect()->route('login')->withErrors(['sso' => __('auth.sso_expired')]);
        }

        // 验证 HMAC 签名——payload 用冒号分隔，杜绝无分隔拼接的歧义
        // （旧格式 '45'.'5victim@x.com' ≡ '455'.'victim@x.com' 可跨用户冒充，
        //  修复后格式 'user_id:email:timestamp' 一一对应，无重解释空间）
        $payload = implode(':', [
            Typed::string($request->input('user_id', '')),
            Typed::string($request->input('email', '')),
            Typed::string($request->input('timestamp')),
        ]);
        $expectedToken = hash_hmac('sha256', $payload, trim(Typed::string($ssoSecret), '"'));

        if (! hash_equals($expectedToken, Typed::string($request->input('token')))) {
            return redirect()->route('login')->withErrors(['sso' => __('auth.sso_invalid_token')]);
        }

        // 防重放（第十一轮）：token 明文出现在 URL query（referrer/访问日志/
        // 浏览器历史可泄露），5 分钟时间窗内重复提交即可冒用登录——
        // 同一 token 仅消费一次，Cache::add 原子占位（含并发双击），
        // TTL 310s > 时间戳容差 300s，覆盖全部有效窗口
        $tokenCacheKey = 'sso_token_used_'.hash('sha256', Typed::string($request->input('token')));
        if (! Cache::add($tokenCacheKey, 1, 310)) {
            return redirect()->route('login')->withErrors(['sso' => __('auth.sso_invalid_token')]);
        }

        // 查找用户
        $user = null;
        if ($request->filled('user_id')) {
            $user = User::query()->where('user_id', Typed::int($request->input('user_id')))->first();
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->input('email'))->first();
        }

        if (! $user || $user->status != 1) {
            return redirect()->route('login')->withErrors(['sso' => __('auth.sso_user_not_found')]);
        }

        // 会话固定防护：SSO 签名验证通过即更换 session id
        $request->session()->regenerate();

        Auth::login($user);
        // 单条原子 SQL：登录计数自增 + 活跃时间，替代两次写
        $user->increment('total_logins', 1, ['last_activity' => now()]);

        return redirect()->intended(route('dashboard'));
    }
}
