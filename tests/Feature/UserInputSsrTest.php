<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 安全审计周期 #17：用户侧输入面（出站请求目标 + 域名 host）
 *
 * 缺陷（修复前）：
 * - POST /push-notifications/subscribe 的 endpoint 仅 required|url：
 *   认证用户可注册 http://192.168.1.1/x 之类内网地址，营销广播发送时
 *   平台向其 POST（WebPushService::send -> curl_init($endpoint)，
 *   发送侧亦无过滤）—— SSRF（内网可达性探测 + 请求注入）
 * - DomainController::store/update 的 host 无格式校验：
 *   javascript:alert(1)、http://127.0.0.1/admin、带路径/空格的垃圾
 *   可入库并流入 whois 监控链路
 */
class UserInputSsrTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'User', 'email' => 'user@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);
    }

    /* ---------------- Push 订阅 SSRF ---------------- */

    public function test_subscribe_rejects_private_ip_endpoint(): void
    {
        $this->actingAs($this->user)
            ->post('/push-notifications/subscribe', [
                'endpoint' => 'http://192.168.1.1/push/xyz',
                'keys' => ['auth' => 'a', 'p256dh' => 'b'],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('endpoint');

        $this->assertDatabaseMissing('push_notifications_subscribers', [
            'user_id' => $this->user->user_id,
        ]);
    }

    public function test_subscribe_rejects_localhost_endpoint(): void
    {
        $this->actingAs($this->user)
            ->post('/push-notifications/subscribe', [
                'endpoint' => 'http://localhost:8080/x',
                'keys' => ['auth' => 'a', 'p256dh' => 'b'],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('endpoint');
    }

    public function test_subscribe_rejects_non_https_endpoint(): void
    {
        // push endpoint 语义上必须是 https（公开 push service）
        $this->actingAs($this->user)
            ->post('/push-notifications/subscribe', [
                'endpoint' => 'http://fcm.googleapis.com/fcm/xyz',
                'keys' => ['auth' => 'a', 'p256dh' => 'b'],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('endpoint');
    }

    public function test_subscribe_accepts_valid_push_endpoint(): void
    {
        $website = \App\Models\Website::create([
            'user_id' => $this->user->user_id,
            'pixel_key' => 'px_t17', 'name' => 'Site', 'scheme' => 'https',
            'host' => 'site.test', 'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $this->actingAs($this->user)
            ->post('/push-notifications/subscribe', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/xyz',
                'website_id' => $website->website_id,
                'keys' => ['auth' => 'a', 'p256dh' => 'b'],
            ])
            ->assertOk();

        $this->assertDatabaseHas('push_notifications_subscribers', [
            'user_id' => $this->user->user_id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/xyz',
        ]);
    }

    public function test_webpush_send_skips_unsafe_endpoint_without_attempt(): void
    {
        // 发送侧纵深防御：存量脏 endpoint 不发起连接（lastResults 保持空，
        // 即「未尝试」而非「尝试失败」）
        $service = app(WebPushService::class);

        $result = $service->send(
            'http://127.0.0.1:9/x',
            rtrim(strtr(base64_encode(str_repeat('a', 65)), '+/', '-_'), '='),
            rtrim(strtr(base64_encode(str_repeat('a', 16)), '+/', '-_'), '='),
            ['title' => 't', 'body' => 'b'],
            'public',
            'private',
        );

        $this->assertFalse($result);
        $this->assertSame([], $service->lastResults);
    }

    /* ---------------- Domain host 校验 ---------------- */

    public function test_domain_store_rejects_url_as_host(): void
    {
        $this->actingAs($this->user)
            ->post('/domains', ['host' => 'http://127.0.0.1/admin'])
            ->assertRedirect()
            ->assertSessionHasErrors('host');

        $this->assertDatabaseMissing('domains', [
            'user_id' => $this->user->user_id,
        ]);
    }

    public function test_domain_store_rejects_javascript_host(): void
    {
        $this->actingAs($this->user)
            ->post('/domains', ['host' => 'javascript:alert(1)'])
            ->assertRedirect()
            ->assertSessionHasErrors('host');
    }

    public function test_domain_store_rejects_host_with_path(): void
    {
        $this->actingAs($this->user)
            ->post('/domains', ['host' => 'example.com/path?q=1'])
            ->assertRedirect()
            ->assertSessionHasErrors('host');
    }

    public function test_domain_store_accepts_valid_host_and_normalizes(): void
    {
        $this->actingAs($this->user)
            ->post('/domains', ['host' => 'Blog.Example-Site.com'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('domains', [
            'user_id' => $this->user->user_id,
            'host' => 'blog.example-site.com',
        ]);
    }

    public function test_domain_update_rejects_invalid_host(): void
    {
        $domain = Domain::create([
            'user_id' => $this->user->user_id,
            'host' => 'example.com', 'scheme' => 'https',
            'is_enabled' => true, 'datetime' => now(),
        ]);

        $this->actingAs($this->user)
            ->put('/domains', [
                'domain_id' => $domain->domain_id,
                'host' => 'javascript:alert(1)',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('host');

        $this->assertSame('example.com', $domain->fresh()->host);
    }
}
