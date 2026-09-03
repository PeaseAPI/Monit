<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (abs(time() - $request->input('timestamp')) > 300) {
            return redirect()->route('login')->withErrors(['sso' => __('auth.sso_expired')]);
        }

        // 验证 HMAC 签名——payload 用冒号分隔，杜绝无分隔拼接的歧义
        // （旧格式 '45'.'5victim@x.com' ≡ '455'.'victim@x.com' 可跨用户冒充，
        //  修复后格式 'user_id:email:timestamp' 一一对应，无重解释空间）
        $payload = implode(':', [
            (string) $request->input('user_id', ''),
            (string) $request->input('email', ''),
            (string) $request->input('timestamp'),
        ]);
        $expectedToken = hash_hmac('sha256', $payload, trim($ssoSecret, '"'));

        if (! hash_equals($expectedToken, $request->input('token'))) {
            return redirect()->route('login')->withErrors(['sso' => __('auth.sso_invalid_token')]);
        }

        // 查找用户
        $user = null;
        if ($request->filled('user_id')) {
            $user = User::find($request->input('user_id'));
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->input('email'))->first();
        }

        if (! $user || $user->status != 1) {
            return redirect()->route('login')->withErrors(['sso' => __('auth.sso_user_not_found')]);
        }

        Auth::login($user);
        $user->increment('total_logins');
        $user->update(['last_activity' => now()]);

        return redirect()->intended(route('dashboard'));
    }
}
