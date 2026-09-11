<?php

namespace App\Services\Social;

use App\Support\Typed;
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

    public function getAuthorizationUrl(?string $state = null): string
    {
        return 'https://gitee.com/oauth/authorize?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'user_info emails',
            'state' => $state ?? csrf_token(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccessToken(string $code): array
    {
        $response = Http::asForm()->post('https://gitee.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'client_secret' => $this->clientSecret,
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
        $token = Typed::string($tokenData['access_token'] ?? $accessToken);

        $response = Http::withToken($token)->get('https://gitee.com/api/v5/user');
        $data = Typed::arr($response->json());

        $email = Typed::stringOrNull($data['email'] ?? null);
        if (empty($email)) {
            $emailResponse = Http::withToken($token)->get('https://gitee.com/api/v5/emails');
            $emails = $emailResponse->json();
            if (is_array($emails) && count($emails) > 0) {
                $primary = collect($emails)->firstWhere('state', 'confirmed');
                $email = data_get($primary, 'email') ?? data_get($emails, '0.email');
            }
        }

        return [
            'id' => Typed::string($data['id'] ?? ''),
            'name' => Typed::string($data['name'] ?? $data['login'] ?? ''),
            'avatar' => Typed::string($data['avatar_url'] ?? ''),
            'email' => $email,
        ];
    }
}
