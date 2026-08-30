<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\Http;

/**
 * Gitee 登录提供商（规格书 §12.3）
 */
class GiteeProvider implements ChineseSocialProvider
{
    public function __construct(
        protected string $clientId,
        protected string $clientSecret,
        protected string $redirectUri,
    ) {}

    public function getAuthorizationUrl(): string
    {
        return 'https://gitee.com/oauth/authorize?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'user_info emails',
            'state' => csrf_token(),
        ]);
    }

    public function getAccessToken(string $code): array
    {
        $response = Http::asForm()->post('https://gitee.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'client_secret' => $this->clientSecret,
        ]);

        return $response->json();
    }

    public function getUserInfo(string $accessToken): array
    {
        $tokenData = json_decode($accessToken, true) ?? [];
        $token = $tokenData['access_token'] ?? $accessToken;

        $response = Http::withToken($token)->get('https://gitee.com/api/v5/user');
        $data = $response->json();

        $email = $data['email'] ?? null;
        if (empty($email)) {
            $emailResponse = Http::withToken($token)->get('https://gitee.com/api/v5/emails');
            $emails = $emailResponse->json();
            if (is_array($emails) && count($emails) > 0) {
                $primary = collect($emails)->firstWhere('state', 'confirmed');
                $email = $primary['email'] ?? ($emails[0]['email'] ?? null);
            }
        }

        return [
            'id' => (string) ($data['id'] ?? ''),
            'name' => $data['name'] ?? ($data['login'] ?? ''),
            'avatar' => $data['avatar_url'] ?? '',
            'email' => $email,
        ];
    }
}
