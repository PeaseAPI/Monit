<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;

/**
 * 飞书登录提供商（规格书 §12.3）
 */
class FeishuProvider implements ChineseSocialProvider
{
    public function __construct(
        protected string $appId,
        protected string $appSecret,
        protected string $redirectUri,
    ) {}

    public function getAuthorizationUrl(?string $state = null): string
    {
        return 'https://open.feishu.cn/open-apis/authen/v1/authorize?'.http_build_query([
            'app_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'state' => $state ?? csrf_token(),
        ]);
    }

    public function getAccessToken(string $code): array
    {
        // 先获取 app_access_token
        $tokenResponse = Http::asForm()->post('https://open.feishu.cn/open-apis/auth/v3/app_access_token/internal', [
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret,
        ]);

        $tokenData = $tokenResponse->json();
        $appAccessToken = $tokenData['app_access_token'] ?? '';

        // 用 app_access_token + code 获取 user_access_token
        $response = Http::withToken($appAccessToken)
            ->asForm()
            ->post('https://open.feishu.cn/open-apis/authen/v1/oidc/access_token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

        return $response->json('data', []);
    }

    public function getUserInfo(string $accessToken): array
    {
        $tokenData = json_decode($accessToken, true) ?? [];
        $userAccessToken = $tokenData['access_token'] ?? $accessToken;

        $response = Http::withToken($userAccessToken)
            ->get('https://open.feishu.cn/open-apis/authen/v1/user_info');

        $data = $response->json('data', []);

        return [
            'id' => $data['user_id'] ?? ($data['open_id'] ?? ''),
            'name' => $data['name'] ?? '',
            'avatar' => $data['avatar_url'] ?? '',
            'email' => $data['email'] ?? ($data['mobile'] ?? null),
        ];
    }
}
