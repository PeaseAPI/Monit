<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 公开统计页（/statistics/{pixel_key}）：渲染 / 密码门 / 套餐开关 / 时间范围
 * 回归：range-switcher 组件曾因 public 页传参不匹配（$website 未定义）导致整页 500
 */
class PublicStatsPageTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(array $websiteSettings = []): Website
    {
        $user = User::create([
            'name' => 'U', 'email' => 'pub@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['websites_public_statistics_is_enabled' => true],
        ]);

        return Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_pub',
            'name' => 'PubSite', 'scheme' => 'https', 'host' => 'pub.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'settings' => $websiteSettings,
        ]);
    }

    public function test_renders_public_stats_page(): void
    {
        $this->fixture();

        $this->get('/statistics/px_pub')->assertOk()
            ->assertSee('PubSite')
            ->assertSee(__('stats.public_stats_title'));
    }

    public function test_public_stats_supports_range_switch(): void
    {
        $this->fixture();

        foreach ([1, 7, 30] as $range) {
            $this->get("/statistics/px_pub?range={$range}")->assertOk();
        }
    }

    public function test_hidden_when_plan_disables_public_stats(): void
    {
        $user = User::create([
            'name' => 'U2', 'email' => 'pub2@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom', 'plan_settings' => [],
        ]);

        Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_hidden',
            'name' => 'Hidden', 'scheme' => 'https', 'host' => 'hidden.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $this->get('/statistics/px_hidden')->assertNotFound();
    }

    public function test_password_gate_renders_when_configured(): void
    {
        $this->fixture(['public_statistics_password' => 'secret123']);

        $this->get('/statistics/px_pub')->assertOk()
            ->assertSee(__('auth.view_statistics'));
    }
}
