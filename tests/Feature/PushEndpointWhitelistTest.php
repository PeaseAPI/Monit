<?php

namespace Tests\Feature;

use App\Services\WebPushService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 安全审计周期 #19 S4：推送服务商 endpoint 域名白名单测试
 *
 * 验证 WebPushService::endpointAllowed() 仅允许已知的浏览器厂商推送服务域名，
 * 拒绝任意外部 endpoint，防止平台被当作 SSRF 跳板。
 */
class PushEndpointWhitelistTest extends TestCase
{
    protected WebPushService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebPushService;
    }

    #[Test]
    public function fcm_googleapis_endpoint_is_allowed(): void
    {
        $this->assertTrue(
            $this->service->isEndpointAllowed('https://fcm.googleapis.com/fcm/send/abc123')
        );
    }

    #[Test]
    public function mozilla_push_endpoint_is_allowed(): void
    {
        $this->assertTrue(
            $this->service->isEndpointAllowed('https://updates.push.services.mozilla.com/wpush/v2/xyz')
        );
    }

    #[Test]
    public function mozilla_aws_endpoint_is_allowed(): void
    {
        $this->assertTrue(
            $this->service->isEndpointAllowed('https://push.prod.mozaws.net/v1/abc')
        );
    }

    #[Test]
    public function apple_push_endpoint_is_allowed(): void
    {
        $this->assertTrue(
            $this->service->isEndpointAllowed('https://web.push.apple.com/v1/push')
        );
    }

    #[Test]
    public function windows_notify_endpoint_is_allowed(): void
    {
        $this->assertTrue(
            $this->service->isEndpointAllowed('https://bn1.notify.windows.com/?token=abc')
        );
    }

    #[Test]
    public function arbitrary_external_endpoint_is_rejected(): void
    {
        $this->assertFalse(
            $this->service->isEndpointAllowed('https://evil.example.com/push')
        );
    }

    #[Test]
    public function internal_network_endpoint_is_rejected(): void
    {
        $this->assertFalse(
            $this->service->isEndpointAllowed('http://192.168.1.1/push')
        );
    }

    #[Test]
    public function empty_host_is_rejected(): void
    {
        $this->assertFalse(
            $this->service->isEndpointAllowed('')
        );
    }

    #[Test]
    public function config_extra_domains_are_respected(): void
    {
        config(['services.webpush.extra_endpoint_domains' => ['my-push.example.com']]);

        $service = new WebPushService;

        $this->assertTrue(
            $service->isEndpointAllowed('https://my-push.example.com/v1/send')
        );

        // 仍然拒绝不在白名单的
        $this->assertFalse(
            $service->isEndpointAllowed('https://other.example.com/push')
        );
    }
}
