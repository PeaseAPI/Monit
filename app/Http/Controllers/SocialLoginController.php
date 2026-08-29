<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 社交登录控制器
 * 规格书 §12.3：Google + GitHub OAuth 2.0（MVP）
 */
class SocialLoginController extends Controller
{
    protected array $providers = [
        'google' => [
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
            'scope' => 'openid email profile',
        ],
        'github' => [
            'authorize_url' => 'https://github.com/login/oauth/authorize',
            'token_url' => 'https://github.com/login/oauth/access_token',
            'userinfo_url' => 'https://api.github.com/user',
            'userinfo_email_url' => 'https://api.github.com/user/emails',
            'scope' => 'user:email',
        ],
    ];

    /**
     * 跳转到社交登录授权页面
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (! isset($this->providers[$provider])) {
            return redirect()->route('login')->withErrors(['provider' => __('auth.unsupported_provider')]);
        }

        $clientId = config("services.{$provider}.client_id");
        if (! $clientId) {
            return redirect()->route('login')->withErrors(['provider' => __('auth.provider_not_configured')]);
        }

        $config = $this->providers[$provider];
        $state = Str::random(40);
        session(["oauth_state_{$provider}" => $state]);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => route('social-login.callback', $provider),
            'scope' => $config['scope'],
            'state' => $state,
            'response_type' => 'code',
        ]);

        return redirect("{$config['authorize_url']}?{$query}");
    }

    /**
     * 处理社交登录回调
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        if (! isset($this->providers[$provider])) {
            return redirect()->route('login')->withErrors(['provider' => __('auth.unsupported_provider')]);
        }

        // 验证 state 防止 CSRF
        $state = $request->input('state');
        if ($state !== session("oauth_state_{$provider}")) {
            return redirect()->route('login')->withErrors(['oauth' => __('auth.oauth_state_mismatch')]);
        }
        session()->forget("oauth_state_{$provider}");

        if ($request->input('error')) {
            return redirect()->route('login')->withErrors(['oauth' => $request->input('error_description', $request->input('error'))]);
        }

        $code = $request->input('code');
        if (! $code) {
            return redirect()->route('login')->withErrors(['oauth' => __('auth.oauth_no_code')]);
        }

        $tokenData = $this->getAccessToken($provider, $code);
        if (! $tokenData || isset($tokenData['error'])) {
            return redirect()->route('login')->withErrors(['oauth' => __('auth.oauth_token_failed')]);
        }

        $userInfo = $this->getUserInfo($provider, $tokenData['access_token']);
        if (! $userInfo || empty($userInfo['email'])) {
            return redirect()->route('login')->withErrors(['oauth' => __('auth.oauth_no_email')]);
        }

                return $this->loginOrRegister($provider, $userInfo);
    }

    /**
     * 用 authorization code 换取 access_token
     */
    protected function getAccessToken(string $provider, string $code): ?array
    {
        $config = $this->providers[$provider];
        $clientId = config("services.{$provider}.client_id");
        $clientSecret = config("services.{$provider}.client_secret");

        try {
            $response = Http::asForm()->post($config['token_url'], [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => route('social-login.callback', $provider),
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);

            return $response->json();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 用 access_token 获取用户信息
     */
    protected function getUserInfo(string $provider, string $accessToken): ?array
    {
        $config = $this->providers[$provider];

        try {
            $response = Http::withToken($accessToken)->get($config['userinfo_url']);
            $data = $response->json();

            // GitHub 需要单独获取邮箱
            if ($provider === 'github' && empty($data['email'])) {
                $emailResponse = Http::withToken($accessToken)
                    ->get($config['userinfo_email_url']);
                $emails = $emailResponse->json();
                $primaryEmail = collect($emails)->firstWhere('primary', true);
                $data['email'] = $primaryEmail['email'] ?? ($emails[0]['email'] ?? null);
            }

            return [
                'id' => (string) ($data['sub'] ?? $data['id']),
                'email' => $data['email'],
                'name' => $data['name'] ?? ($data['login'] ?? ''),
                'avatar' => $data['picture'] ?? ($data['avatar_url'] ?? null),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

        /**
     * 登录或注册用户
     */
    protected function loginOrRegister(string $provider, array $userInfo): RedirectResponse
    {
        $email = strtolower($userInfo['email']);
        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->status !== 1) {
                return redirect()->route('login')->withErrors(['email' => __('validation.account_disabled')]);
            }

            Auth::login($user, true);
            $user->forceFill(['last_activity' => now(), 'total_logins' => $user->total_logins + 1])->save();

            return redirect()->intended(route('dashboard'));
        }

        // 新用户 - 自动注册，处理推荐码
        $referredBy = null;
        if ($ref = session('referral_key')) {
            $referrer = User::where('referral_key', $ref)->first();
            if ($referrer) {
                $referredBy = $referrer->user_id;
            }
        }

        $user = User::create([
            'type' => 0,
            'name' => $userInfo['name'] ?: 'User',
            'email' => $email,
            'password' => bcrypt(Str::random(32)),
            'plan_id' => 'free',
            'referral_key' => Str::random(32),
            'api_key' => Str::random(60),
            'language' => 'zh_CN',
            'timezone' => 'Asia/Shanghai',
            'status' => 1,
            'ip' => request()->ip(),
            'source' => $provider,
            'avatar' => $userInfo['avatar'],
            'referred_by' => $referredBy,
        ]);

        Auth::login($user, true);
        $user->forceFill(['last_activity' => now(), 'total_logins' => 1])->save();

        return redirect()->route('dashboard')
            ->with('success', __('msg.welcome_monit'));
    }
}