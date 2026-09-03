<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;

/**
 * 微信登录提供商（规格书 §12.3）
 */
class WeChatProvider implements ChineseSocialProvider
{
    public function __construct(
        protected string $appId,
        protected string $appSecret,
        protected string $redirectUri,
    ) {}

    public function getAuthorizationUrl(?string $state = null): string
    {
        return 'https://open.weixin.qq.com/connect/qrconnect?'.http_build_query([
            'appid' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => $state ?? csrf_token(),
        ]).'#wechat_redirect';
    }

    public function getAccessToken(string $code): array
    {
        $response = Http::asForm()->get('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        return $response->json();
    }

    public function getUserInfo(string $accessToken): array
    {
        // 微信的 accessToken 参数需要从 getAccessToken 获取 openid
        // 这里假设 accessToken 实际上是包含 openid 的 JSON 编码字符串
        $tokenData = json_decode($accessToken, true) ?? [];
        $openid = $tokenData['openid'] ?? '';

        $response = Http::get('https://api.weixin.qq.com/sns/userinfo', [
            'access_token' => $tokenData['access_token'] ?? $accessToken,
            'openid' => $openid,
        ]);

        $data = $response->json();

        return [
            'id' => $data['unionid'] ?? ($data['openid'] ?? $openid),
            'name' => $data['nickname'] ?? '',
            'avatar' => $data['headimgurl'] ?? '',
            'email' => null, // 微信不提供邮箱
        ];
    }
}
