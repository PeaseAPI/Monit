<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 任务 E：后台对标原版（monit.cn/admin）布局重写
 * - 侧栏菜单结构（分组/折叠组/顺序）与原版一致
 * - 仪表台 8 张统计卡 + 最新用户表
 * - 设置页新增「地图」组：百度/谷歌/内置 SVG 供应商可配置
 * - 统计页地图组件按供应商渲染（key 缺失自动回退内置 SVG）
 */
class AdminRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'type' => 1, 'plan_id' => 'custom', 'plan_settings' => [],
        ]);
    }

    public function test_admin_dashboard_sidebar_matches_reference_menu(): void
    {
        $this->actingAs($this->adminUser());

        $response = $this->get(route('admin.index'));
        $response->assertOk();

        // 原版侧栏全部菜单项（顺序分组一致）
        foreach ([
            route('admin.users.index'), route('admin.settings.index'), route('admin.plans.index'),
            route('admin.languages.index'), route('admin.broadcasts.index'), route('admin.notifications.index'),
            route('admin.push-notifications.index'), route('admin.plugins.index'), route('admin.statistics'),
            route('admin.pages-categories.index'), route('admin.pages.index'),
            route('admin.blog-posts-categories.index'), route('admin.blog-posts.index'),
            route('admin.codes.index'), route('admin.taxes.index'), route('admin.payments.index'),
            route('admin.affiliates-withdrawals.index'),
            route('admin.websites.index'), route('admin.heatmaps.index'), route('admin.replays.index'),
            route('admin.annotations.index'), route('admin.domains.index'),
            route('admin.users.logs'),
        ] as $url) {
            $response->assertSee('href="'.$url.'"', false);
        }

        // 折叠组容器（对标 admin_sidebar_resources/blog_container）
        $response->assertSee('id="admin-group-resources"', false)
            ->assertSee('id="admin-group-blog"', false)
            // 侧栏底部用户菜单（对标 admin-sidebar-footer）
            ->assertSee('id="admin-user-menu"', false)
            ->assertSee(__('admin.user_panel'));
    }

    public function test_admin_dashboard_renders_stat_cards_and_tables(): void
    {
        $this->actingAs($this->adminUser());

        $response = $this->get(route('admin.index'));
        $response->assertOk();

        // 8 张统计卡标签（对标原版卡序）+ 本月增量 + 活跃用户
        foreach (['admin.stat_websites', 'admin.stat_replays', 'admin.stat_heatmaps', 'admin.stat_goals',
            'admin.stat_domains', 'admin.stat_users', 'admin.stat_payments', 'admin.monthly_revenue'] as $key) {
            $response->assertSee(__($key));
        }
        $response->assertSee(__('admin.stat_this_month'))
            ->assertSee(__('admin.recent_users'))
            ->assertSee(__('admin.recent_payments'))
            ->assertSee(__('admin.col_status'));
    }

    public function test_admin_settings_contains_maps_tab(): void
    {
        $this->actingAs($this->adminUser());

        $response = $this->get(route('admin.settings.index'));
        $response->assertOk()
            ->assertSee(__('admin.settings_maps'))
            ->assertSee('name="provider"', false)
            ->assertSee('name="baidu_key"', false)
            ->assertSee('name="google_key"', false)
            ->assertSee(__('admin.maps_provider_baidu'))
            ->assertSee(__('admin.maps_provider_google'));
    }

    public function test_maps_provider_setting_is_saved_and_applied(): void
    {
        $this->actingAs($this->adminUser());

        // 默认：内置 SVG 世界地图（无外部依赖，国内可用）
        $this->get(route('admin.statistics'))->assertOk()
            ->assertSee('vendor/svgmap/svgMap.min.js', false);

        // 保存百度地图供应商 + AK
        $this->put(route('admin.settings.update'), [
            'group' => 'maps',
            'provider' => 'baidu',
            'baidu_key' => 'TEST_BAIDU_AK',
            'google_key' => '',
        ])->assertRedirect();

        $this->assertSame('baidu', Settings::get('maps.provider'));
        $this->assertSame('TEST_BAIDU_AK', Settings::get('maps.baidu_key'));

        // 统计页按百度渲染：加载百度 API 并打点
        $this->get(route('admin.statistics'))->assertOk()
            ->assertSee('api.map.baidu.com/api?v=3.0&ak=TEST_BAIDU_AK', false)
            ->assertSee('BMap.Map', false);

        // 清空 AK：自动回退内置 SVG，避免空白
        $this->put(route('admin.settings.update'), [
            'group' => 'maps',
            'provider' => 'baidu',
            'baidu_key' => '',
            'google_key' => '',
        ])->assertRedirect();

        $this->get(route('admin.statistics'))->assertOk()
            ->assertSee('vendor/svgmap/svgMap.min.js', false)
            ->assertDontSee('api.map.baidu.com', false);
    }

    public function test_maps_google_provider_renders_google_script(): void
    {
        $this->actingAs($this->adminUser());

        Setting::create(['key' => 'maps.provider', 'value' => 'google']);
        Setting::create(['key' => 'maps.google_key', 'value' => 'TEST_G_KEY']);
        Settings::flush();

        $this->get(route('admin.statistics'))->assertOk()
            ->assertSee('maps.googleapis.com/maps/api/js?key=TEST_G_KEY', false);
    }
}
