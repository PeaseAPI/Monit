<?php

namespace App\Services\Social;

use App\Support\Typed;
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

    /**
     * @return array<string, mixed>
     */
    public function getAccessToken(string $code): array
    {
        $response = Http::asForm()->get('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
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
        // 微信的 accessToken 参数需要从 getAccessToken 获取 openid
        // 这里假设 accessToken 实际上是包含 openid 的 JSON 编码字符串
        $tokenData = Typed::arr(json_decode($accessToken, true));
        $openid = Typed::string($tokenData['openid'] ?? '');

        $response = Http::get('https://api.weixin.qq.com/sns/userinfo', [
            'access_token' => Typed::string($tokenData['access_token'] ?? $accessToken),
            'openid' => $openid,
        ]);

        $data = Typed::arr($response->json());

        return [
            'id' => Typed::string($data['unionid'] ?? $data['openid'] ?? $openid),
            'name' => Typed::string($data['nickname'] ?? ''),
            'avatar' => Typed::string($data['headimgurl'] ?? ''),
            'email' => null, // 微信不提供邮箱
        ];
    }
}
