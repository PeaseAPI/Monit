<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;

/**
 * 微博登录提供商（规格书 §12.3）
 */
class WeiboProvider implements ChineseSocialProvider
{
    public function __construct(
        protected string $appKey,
        protected string $appSecret,
        protected string $redirectUri,
    ) {}

    public function getAuthorizationUrl(): string
    {
        return 'https://api.weibo.com/oauth2/authorize?'.http_build_query([
            'client_id' => $this->appKey,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email',
            'state' => csrf_token(),
        ]);
    }

    public function getAccessToken(string $code): array
    {
        $response = Http::asForm()->post('https://api.weibo.com/oauth2/access_token', [
            'client_id' => $this->appKey,
            'client_secret' => $this->appSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        return $response->json();
    }

    public function getUserInfo(string $accessToken): array
    {
        $tokenData = json_decode($accessToken, true) ?? [];
        $uid = $tokenData['uid'] ?? '';

        $response = Http::get('https://api.weibo.com/2/users/show.json', [
            'access_token' => $tokenData['access_token'] ?? $accessToken,
            'uid' => $uid,
        ]);

        $data = $response->json();

        return [
            'id' => (string) ($data['id'] ?? $uid),
            'name' => $data['screen_name'] ?? ($data['name'] ?? ''),
            'avatar' => $data['avatar_large'] ?? ($data['profile_image_url'] ?? ''),
            'email' => $data['email'] ?? null,
        ];
    }
}
