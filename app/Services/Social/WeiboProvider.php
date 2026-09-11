<?php

namespace App\Services\Social;

use App\Support\Typed;
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

    public function getAuthorizationUrl(?string $state = null): string
    {
        return 'https://api.weibo.com/oauth2/authorize?'.http_build_query([
            'client_id' => $this->appKey,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email',
            'state' => $state ?? csrf_token(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccessToken(string $code): array
    {
        $response = Http::asForm()->post('https://api.weibo.com/oauth2/access_token', [
            'client_id' => $this->appKey,
            'client_secret' => $this->appSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        $json = $response->json();

        /** @var array<string, mixed> $json */
        $json = is_array($json) ? $json : [];

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserInfo(string $accessToken): array
    {
        $tokenData = Typed::arr(json_decode($accessToken, true));
        $uid = Typed::string($tokenData['uid'] ?? '');

        $response = Http::get('https://api.weibo.com/2/users/show.json', [
            'access_token' => Typed::string($tokenData['access_token'] ?? $accessToken),
            'uid' => $uid,
        ]);

        $data = Typed::arr($response->json());

        return [
            'id' => Typed::string($data['id'] ?? $uid),
            'name' => Typed::string($data['screen_name'] ?? $data['name'] ?? ''),
            'avatar' => Typed::string($data['avatar_large'] ?? $data['profile_image_url'] ?? ''),
            'email' => Typed::stringOrNull($data['email'] ?? null),
        ];
    }
}
