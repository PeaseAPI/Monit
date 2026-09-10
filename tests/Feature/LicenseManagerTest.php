<?php

namespace Tests\Feature;

use App\Services\LicenseManager;
use Tests\TestCase;

/**
 * 第十六轮：LicenseManager 健壮性
 *
 * 公钥未配置/格式非法时验签必须优雅降级为 false（valid=false），
 * 而非抛出未捕获的 SodiumException 导致管理后台设置/授权页 500。
 */
class LicenseManagerTest extends TestCase
{
    public function test_verify_signature_gracefully_fails_when_public_key_not_configured(): void
    {
        config(['monit.license.public_key' => '']);

        $license = [
            'product' => LicenseManager::PRODUCT,
            'domains' => ['example.com'],
            'expires' => '2099-12-31',
            'signature' => str_repeat('ab', SODIUM_CRYPTO_SIGN_BYTES),
        ];

        $this->assertFalse(LicenseManager::verifySignature($license));
    }

    public function test_verify_signature_gracefully_fails_when_public_key_malformed(): void
    {
        config(['monit.license.public_key' => 'zzzz-not-hex']);

        $license = [
            'product' => LicenseManager::PRODUCT,
            'signature' => str_repeat('ab', SODIUM_CRYPTO_SIGN_BYTES),
        ];

        $this->assertFalse(LicenseManager::verifySignature($license));
    }

    public function test_status_returns_missing_when_license_file_absent(): void
    {
        config(['monit.license.path' => storage_path('app/nonexistent-license-test.json')]);

        $status = LicenseManager::status(true); // refresh=true 绕过缓存

        $this->assertFalse($status['valid']);
        $this->assertSame('missing', $status['reason']);
    }
}
