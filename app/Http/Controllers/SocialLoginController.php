<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Social\FeishuProvider;
use App\Services\Social\GiteeProvider;
use App\Services\Social\QQProvider;
use App\Services\Social\WeChatProvider;
use App\Services\Social\WeiboProvider;
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
        'facebook' => [
            'authorize_url' => 'https://www.facebook.com/v18.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'userinfo_url' => 'https://graph.facebook.com/me',
            'userinfo_fields' => 'id,name,email,picture.width(200).height(200)',
            'scope' => 'email,public_profile',
        ],
        'discord' => [
            'authorize_url' => 'https://discord.com/api/oauth2/authorize',
            'token_url' => 'https://discord.com/api/oauth2/token',
            'userinfo_url' => 'https://discord.com/api/users/@me',
            'scope' => 'identify email',
        ],
        'linkedin' => [
            'authorize_url' => 'https://www.linkedin.com/oauth/v2/authorization',
            'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
            'userinfo_url' => 'https://api.linkedin.com/v2/userinfo',
            'scope' => 'openid profile email',
        ],
        'microsoft' => [
            'authorize_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'userinfo_url' => 'https://graph.microsoft.com/oidc/userinfo',
            'scope' => 'openid email profile User.Read',
        ],
        'apple' => [
            'authorize_url' => 'https://appleid.apple.com/auth/authorize',
            'token_url' => 'https://appleid.apple.com/auth/token',
            'userinfo_url' => '', // Apple 使用 id_token (JWT) 而非 userinfo 端点
            'scope' => 'email name',
        ],
        'twitter' => [
            'authorize_url' => 'https://twitter.com/i/oauth2/authorize',
            'token_url' => 'https://api.twitter.com/2/oauth2/token',
            'userinfo_url' => 'https://api.twitter.com/2/users/me',
            'userinfo_fields' => 'user.fields(id,name,profile_image_url)',
            'scope' => 'tweet.read users.read',
        ],
    ];

    /**
     * 国内社交登录提供商映射（规格书 §12.3）
     * 通过服务容器延迟实例化，避免未配置时报错
     */
    protected array $chineseProviders = [
        'qq' => QQProvider::class,
        'wechat' => WeChatProvider::class,
        'weibo' => WeiboProvider::class,
        'gitee' => GiteeProvider::class,
        'feishu' => FeishuProvider::class,
    ];

    /**
     * 跳转到社交登录授权页面
     */
    public function redirect(string $provider): RedirectResponse
    {
        // 国内社交登录提供商
        if (isset($this->chineseProviders[$provider])) {
            return $this->redirectChinese($provider);
        }

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

        $query = [
            'client_id' => $clientId,
            'redirect_uri' => route('social-login.callback', $provider),
            'scope' => $config['scope'],
            'state' => $state,
            'response_type' => 'code',
        ];

        // Discord/Twitter OAuth2 PKCE
        if (in_array($provider, ['discord', 'twitter'])) {
            $codeVerifier = Str::random(128);
            $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
            session(["oauth_code_verifier_{$provider}" => $codeVerifier]);
            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = 'S256';
        }

        // Apple 特殊参数
        if ($provider === 'apple') {
            $query['response_mode'] = 'form_post';
        }

        return redirect("{$config['authorize_url']}?".http_build_query($query));
    }

    /**
     * 国内社交登录跳转（规格书 §12.3）
     */
    protected function redirectChinese(string $provider): RedirectResponse
    {
        $config = $this->getChineseProviderConfig($provider);
        if (! $config) {
            return redirect()->route('login')->withErrors(['provider' => __('auth.provider_not_configured')]);
        }

        $providerInstance = new $this->chineseProviders[$provider](
            ...array_values($config)
        );

        $state = Str::random(40);
        session(["oauth_state_{$provider}" => $state]);

        return redirect($providerInstance->getAuthorizationUrl($state));
    }

    /**
     * 处理社交登录回调
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        // 国内社交登录回调
        if (isset($this->chineseProviders[$provider])) {
            return $this->callbackChinese($provider, $request);
        }

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
     * 国内社交登录回调处理（规格书 §12.3）
     */
    protected function callbackChinese(string $provider, Request $request): RedirectResponse
    {
        // 验证 state 防止 CSRF（Login CSRF / code 注入）——与 callback() 同标准。
        // 此前国内 5 家完全缺失校验，攻击者可将自身 code 注入受害者浏览器完成登录。
        $state = $request->input('state');
        if (! $state || ! hash_equals((string) session("oauth_state_{$provider}"), (string) $state)) {
            return redirect()->route('login')->withErrors(['oauth' => __('auth.oauth_state_mismatch')]);
        }
        session()->forget("oauth_state_{$provider}");

        $config = $this->getChineseProviderConfig($provider);
        if (! $config) {
            return redirect()->route('login')->withErrors(['provider' => __('auth.provider_not_configured')]);
        }

        if ($request->input('error')) {
            return redirect()->route('login')->withErrors(['oauth' => $request->input('error_description', $request->input('error'))]);
        }

        $code = $request->input('code');
        if (! $code) {
            return redirect()->route('login')->withErrors(['oauth' => __('auth.oauth_no_code')]);
        }

        $providerInstance = new $this->chineseProviders[$provider](
            ...array_values($config)
        );

        $tokenData = $providerInstance->getAccessToken($code);
        if (! $tokenData || isset($tokenData['error'])) {
            return redirect()->route('login')->withErrors(['oauth' => __('auth.oauth_token_failed')]);
        }

        $userInfo = $providerInstance->getUserInfo(json_encode($tokenData));

        // 国内提供商可能不提供邮箱，允许空邮箱但需要标识符
        if (! $userInfo || empty($userInfo['id'])) {
            return redirect()->route('login')->withErrors(['oauth' => __('auth.oauth_no_email')]);
        }

        // 如果没有邮箱，用 provider+id 构造一个虚拟邮箱
        if (empty($userInfo['email'])) {
            $userInfo['email'] = $provider.'_'.$userInfo['id'].'@social.login';
        }

        return $this->loginOrRegister($provider, $userInfo);
    }

    /**
     * 获取国内社交登录配置（规格书 §12.3）
     */
    protected function getChineseProviderConfig(string $provider): ?array
    {
        return match ($provider) {
            'qq' => [
                'appId' => config('services.qq.client_id'),
                'appKey' => config('services.qq.client_secret'),
                'redirectUri' => route('social-login.callback', 'qq'),
            ],
            'wechat' => [
                'appId' => config('services.wechat.client_id'),
                'appSecret' => config('services.wechat.client_secret'),
                'redirectUri' => route('social-login.callback', 'wechat'),
            ],
            'weibo' => [
                'appKey' => config('services.weibo.client_id'),
                'appSecret' => config('services.weibo.client_secret'),
                'redirectUri' => route('social-login.callback', 'weibo'),
            ],
            'gitee' => [
                'clientId' => config('services.gitee.client_id'),
                'clientSecret' => config('services.gitee.client_secret'),
                'redirectUri' => route('social-login.callback', 'gitee'),
            ],
            'feishu' => [
                'appId' => config('services.feishu.client_id'),
                'appSecret' => config('services.feishu.client_secret'),
                'redirectUri' => route('social-login.callback', 'feishu'),
            ],
            default => null,
        };
    }

    /**
     * 用 authorization code 换取 access_token
     */
    protected function getAccessToken(string $provider, string $code): ?array
    {
        $config = $this->providers[$provider];
        $clientId = config("services.{$provider}.client_id");
        $clientSecret = config("services.{$provider}.client_secret");

        $params = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => route('social-login.callback', $provider),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];

        // PKCE providers
        if (in_array($provider, ['discord', 'twitter']) && session("oauth_code_verifier_{$provider}")) {
            $params['code_verifier'] = session("oauth_code_verifier_{$provider}");
        }

        // Apple 需要 client_secret 为 JWT
        if ($provider === 'apple') {
            $params['client_secret'] = $this->generateAppleClientSecret($clientId);
        }

        try {
            $response = Http::asForm()->post($config['token_url'], $params);

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
            // Apple 使用 id_token 解码而非 userinfo 端点
            if ($provider === 'apple') {
                return $this->getAppleUserInfo($accessToken);
            }

            $queryParams = [];
            if ($provider === 'facebook') {
                $queryParams['fields'] = $config['userinfo_fields'] ?? 'id,name,email,picture.width(200).height(200)';
            }

            $request = Http::withToken($accessToken);

            // Twitter 需要 Bearer + oauth2 只读
            if ($provider === 'twitter') {
                $queryParams = ['user.fields' => 'id,name,profile_image_url'];
            }

            $response = $request->get($config['userinfo_url'], $queryParams);
            $data = $response->json();

            // GitHub 需要单独获取邮箱
            if ($provider === 'github' && empty($data['email'])) {
                $emailResponse = Http::withToken($accessToken)
                    ->get($config['userinfo_email_url']);
                $emails = $emailResponse->json();
                $primaryEmail = collect($emails)->firstWhere('primary', true);
                $data['email'] = $primaryEmail['email'] ?? ($emails[0]['email'] ?? null);
            }

            // Discord 用户信息
            if ($provider === 'discord') {
                return [
                    'id' => (string) $data['id'],
                    'email' => $data['email'] ?? null,
                    'name' => $data['global_name'] ?? ($data['username'] ?? ''),
                    'avatar' => isset($data['avatar'])
                        ? "https://cdn.discordapp.com/avatars/{$data['id']}/{$data['avatar']}.png"
                        : null,
                ];
            }

            // Twitter 用户信息（嵌套 data 对象）
            if ($provider === 'twitter') {
                $userData = $data['data'] ?? $data;

                return [
                    'id' => (string) ($userData['id'] ?? ''),
                    'email' => null, // Twitter v2 不提供邮箱
                    'name' => $userData['name'] ?? '',
                    'avatar' => $userData['profile_image_url'] ?? null,
                ];
            }

            // Facebook 用户信息
            if ($provider === 'facebook') {
                return [
                    'id' => (string) $data['id'],
                    'email' => $data['email'] ?? null,
                    'name' => $data['name'] ?? '',
                    'avatar' => $data['picture']['data']['url'] ?? null,
                ];
            }

            // LinkedIn / Microsoft / Google 通用格式
            return [
                'id' => (string) ($data['sub'] ?? $data['id']),
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? ($data['login'] ?? ''),
                'avatar' => $data['picture'] ?? ($data['avatar_url'] ?? null),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Apple id_token 解码获取用户信息（规格书 §12.3）
     */
    protected function getAppleUserInfo(string $idToken): ?array
    {
        try {
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                return null;
            }
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            // 校验 aud：只接受签给本应用 client_id 的 id_token，防止其他 Apple
            // 应用的令牌跨应用重放（纵深防御，state 校验已挡主要注入路径）
            $clientId = config('services.apple.client_id');
            if (! $clientId || ($payload['aud'] ?? null) !== $clientId) {
                return null;
            }

            return [
                'id' => $payload['sub'] ?? '',
                'email' => $payload['email'] ?? null,
                'name' => '', // Apple 仅在首次授权时通过 form_post 提供 name
                'avatar' => null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 生成 Apple Client Secret JWT（规格书 §12.3）
     */
    protected function generateAppleClientSecret(string $clientId): string
    {
        $teamId = config('services.apple.team_id', '');
        $keyId = config('services.apple.key_id', '');
        $privateKey = config('services.apple.private_key', '');

        $header = base64_encode(json_encode(['alg' => 'ES256', 'kid' => $keyId]));
        $payload = base64_encode(json_encode([
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + 86400 * 180,
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ]));

        // 简化实现：使用 openssl_sign
        $signature = '';
        if ($privateKey) {
            openssl_sign("$header.$payload", $signature, $privateKey, OPENSSL_ALGO_SHA256);
        }

        return "$header.$payload.".rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
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
