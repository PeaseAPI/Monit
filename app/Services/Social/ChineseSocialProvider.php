<?php

namespace App\Services\Social;

/**
 * 国内社交登录统一接口（规格书 §12.3：ChineseSocialProvider）
 */
interface ChineseSocialProvider
{
    /**
     * 授权跳转 URL；$state 由控制器生成（每会话随机），回调时必须原样校验
     */
    public function getAuthorizationUrl(?string $state = null): string;

    public function getAccessToken(string $code): array;

    public function getUserInfo(string $accessToken): array;
}
