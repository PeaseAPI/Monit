<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 安全审计周期 #15：API v1 用户侧资源端点租户隔离
 *
 * 缺陷（修复前）：
 * - Api/v1/WebsiteController show/update/destroy 无所有权检查：
 *   任意 API Key 可读/改/删任意用户网站（含不可逆删除）
 * - AnalyticsController 全部端点（realtime/visitors/events/metrics/
 *   top 系列与 pageviews/replays）无授权：任意 API Key 可读取任意网站分析数据
 *
 * 修复：与兄弟控制器一致的 authorizeWebsite（所有者或 admin）
 */
class ApiV1TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $attacker;

    private Website $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner', 'email' => 'owner@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
            'api_key' => 'key_owner_15',
        ]);
        $this->attacker = User::create([
            'name' => 'Attacker', 'email' => 'attacker@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
            'api_key' => 'key_attacker_15',
        ]);
        $this->target = Website::create([
            'user_id' => $this->owner->user_id,
            'pixel_key' => 'px_t15', 'name' => 'Target Site', 'scheme' => 'https',
            'host' => 'target.test', 'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);
    }

    public function test_website_show_is_tenant_isolated(): void
    {
        $this->withToken('key_attacker_15')
            ->getJson("/api/v1/websites/{$this->target->website_id}")
            ->assertStatus(403);
    }

    public function test_website_update_is_tenant_isolated(): void
    {
        $this->withToken('key_attacker_15')
            ->putJson("/api/v1/websites/{$this->target->website_id}", [
                'name' => 'Hacked',
            ])->assertStatus(403);

        $this->assertSame('Target Site', $this->target->fresh()->name);
    }

    public function test_website_destroy_is_tenant_isolated(): void
    {
        $this->withToken('key_attacker_15')
            ->deleteJson("/api/v1/websites/{$this->target->website_id}")
            ->assertStatus(403);

        $this->assertNotNull($this->target->fresh());
    }

    public function test_analytics_realtime_is_tenant_isolated(): void
    {
        $this->withToken('key_attacker_15')
            ->getJson("/api/v1/websites/{$this->target->website_id}/realtime")
            ->assertStatus(403);
    }

    public function test_analytics_metrics_is_tenant_isolated(): void
    {
        $this->withToken('key_attacker_15')
            ->getJson("/api/v1/websites/{$this->target->website_id}/metrics")
            ->assertStatus(403);
    }

    public function test_owner_can_access_own_website(): void
    {
        $this->withToken('key_owner_15')
            ->getJson("/api/v1/websites/{$this->target->website_id}")
            ->assertStatus(200)
            ->assertJsonPath('name', 'Target Site');
    }

    public function test_admin_is_exempt_from_tenant_isolation(): void
    {
        User::create([
            'name' => 'Admin', 'email' => 'admin@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
            'type' => 1, 'api_key' => 'key_admin_15',
        ]);

        $this->withToken('key_admin_15')
            ->getJson("/api/v1/websites/{$this->target->website_id}")
            ->assertStatus(200);
    }
}
