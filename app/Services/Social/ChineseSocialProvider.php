<?php

namespace App\Services\Social;

/**
 * 国内社交登录统一接口（规格书 §12.3：ChineseSocialProvider）
 */
interface ChineseSocialProvider
{
    public function getAuthorizationUrl(): string;

    public function getAccessToken(string $code): array;

    public function getUserInfo(string $accessToken): array;
}
