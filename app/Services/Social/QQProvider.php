<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;

/**
 * QQ 登录提供商（规格书 §12.3）
 */
class QQProvider implements ChineseSocialProvider
{
    public function __construct(
        protected string $appId,
        protected string $appKey,
        protected string $redirectUri,
    ) {}

    public function getAuthorizationUrl(): string
    {
        return 'https://graph.qq.com/oauth2.0/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'get_user_info',
            'state' => csrf_token(),
        ]);
    }

    public function getAccessToken(string $code): array
    {
        $response = Http::asForm()->post('https://graph.qq.com/oauth2.0/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->appId,
            'client_secret' => $this->appKey,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        parse_str($response->body(), $params);

        return $params;
    }

    public function getUserInfo(string $accessToken): array
    {
        // 获取 openid
        $meResponse = Http::get('https://graph.qq.com/oauth2.0/me', [
            'access_token' => $accessToken,
        ]);

        $meBody = $meResponse->body();
        // QQ 返回 callback( {"client_id":"...","openid":"..."} ); 格式
        preg_match('/\((.+)\)/', $meBody, $matches);
        $meData = json_decode($matches[1] ?? '{}', true);

        $openid = $meData['openid'] ?? '';

        // 获取用户信息
        $infoResponse = Http::get('https://graph.qq.com/user/get_user_info', [
            'access_token' => $accessToken,
            'oauth_consumer_key' => $this->appId,
            'openid' => $openid,
        ]);

        $data = $infoResponse->json();

        return [
            'id' => $openid,
            'name' => $data['nickname'] ?? '',
            'avatar' => $data['figureurl_qq_2'] ?? ($data['figureurl_qq_1'] ?? ''),
            'email' => null, // QQ 不提供邮箱
        ];
    }
}
