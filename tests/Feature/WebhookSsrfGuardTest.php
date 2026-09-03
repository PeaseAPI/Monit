<?php

namespace Tests\Feature;

use App\Services\WebhookService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 安全审计周期 #10：出站 Webhook SSRF 防御纵深
 *
 * 背景：isSafeHttpUrl 此前仅做 scheme 过滤（http/https），未拦截
 * 环回/私网/链路本地目标 —— 管理员可配置 webhook URL 让平台服务器
 * 向 127.0.0.1、192.168.x、169.254.169.254（云元数据端点）等发起请求
 * （app 服务器网络权限 ≠ 管理员账号权限，内网探测/云凭证读取属越权面）。
 *
 * 防护语义：
 * - 字面 IP / 可解析域名命中私有/保留网段 → 拒绝派发（fail-closed）
 * - DNS 解析失败（离线环境）→ 放行（不阻断正常功能；重绑定攻击为已知残余风险）
 * - 部署级逃生阀 services.webhooks.allow_private_targets（默认关）供本地调试
 */
class WebhookSsrfGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::flush();
        config(['services.webhooks.allow_private_targets' => false]);
        Http::fake();
    }

    #[Test]
    public function loopback_ipv4_target_is_rejected(): void
    {
        Settings::set('webhooks.webhook_payment_success_url', 'http://127.0.0.1:9000/hook');

        app(WebhookService::class)->paymentSuccess(['payment_id' => 1]);

        Http::assertNothingSent();
    }

    #[Test]
    public function private_network_target_is_rejected(): void
    {
        Settings::set('webhooks.webhook_payment_success_url', 'http://192.168.1.10/hook');

        app(WebhookService::class)->paymentSuccess(['payment_id' => 1]);

        Http::assertNothingSent();
    }

    #[Test]
    public function cloud_metadata_endpoint_is_rejected(): void
    {
        // AWS/GCP/Azure IMDS（169.254.169.254）— SSRF 最高价值目标
        Settings::set('webhooks.webhook_payment_success_url', 'http://169.254.169.254/latest/meta-data/iam/security-credentials/');

        app(WebhookService::class)->paymentSuccess(['payment_id' => 1]);

        Http::assertNothingSent();
    }

    #[Test]
    public function loopback_ipv6_target_is_rejected(): void
    {
        Settings::set('webhooks.webhook_user_register_url', 'http://[::1]/hook');

        app(WebhookService::class)->userRegister(['user_id' => 1]);

        Http::assertNothingSent();
    }

    #[Test]
    public function hostname_resolving_to_loopback_is_rejected(): void
    {
        // localhost 经 /etc/hosts 解析为 127.0.0.1（跨环境稳定）
        Settings::set('webhooks.webhook_user_delete_url', 'http://localhost/hook');

        app(WebhookService::class)->userDelete(['user_id' => 1]);

        Http::assertNothingSent();
    }

    #[Test]
    public function public_target_still_dispatches(): void
    {
        // 强化不得误伤公网目标（回归；与 WebhookDispatchTest 行为一致）
        Settings::set('webhooks.webhook_payment_success_url', 'https://example.com/hook');

        app(WebhookService::class)->paymentSuccess(['payment_id' => 1]);

        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/hook');
    }

    #[Test]
    public function private_target_allowed_when_explicit_override_enabled(): void
    {
        // 部署级逃生阀（本地调试 webhook 接收端）
        config(['services.webhooks.allow_private_targets' => true]);
        Settings::set('webhooks.webhook_payment_success_url', 'http://127.0.0.1:9000/hook');

        app(WebhookService::class)->paymentSuccess(['payment_id' => 1]);

        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:9000/hook');
    }

    #[Test]
    public function bare_url_path_cron_start_is_guarded_too(): void
    {
        // postJson（start_url/end_url 裸 URL 路径）同样受 SSRF 防护
        Settings::set('webhooks.start_url', 'http://10.0.0.5/cron');

        app(WebhookService::class)->cronStart();

        Http::assertNothingSent();
    }
}
