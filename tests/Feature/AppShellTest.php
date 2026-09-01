<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Tests\TestCase;

/**
 * 登录后界面复刻（对标 monit.cn）：
 * 顶部网站选择器 / 用户菜单 / 侧边统计入口 / 网站切换 / 账户头像·防钓鱼码·账单信息
 */
class AppShellTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Shell Tester',
            'email' => 'shell@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => 'free',
        ], $overrides));
    }

    protected function makeWebsite(User $user, string $host): Website
    {
        return Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_'.str_replace('.', '_', $host),
            'name' => 'Site '.$host,
            'scheme' => 'https',
            'host' => $host,
            'tracking_type' => 'advanced',
            'is_enabled' => true,
            'excluded_ips' => '',
            'datetime' => now(),
        ]);
    }

    public function test_topbar_shows_website_switcher_and_user_menu(): void
    {
        $user = $this->makeUser();
        $a = $this->makeWebsite($user, 'a.test');
        $this->makeWebsite($user, 'b.test');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        // 网站选择器：含两个网站的切换链接 + 搜索 + 新建/管理入口
        $response->assertSee('data-topbar-details', false)
            ->assertSee('website-switch/'.$a->website_id, false)
            ->assertSee('topbar-website-list', false)
            ->assertSee(__('topbar.search_website'))
            ->assertSee(__('topbar.add_website'))
            ->assertSee(__('topbar.manage_websites'));
        // 用户菜单：monit.cn 项（账户/偏好/套餐/支付/推荐/API/团队）+ 注销
        $response->assertSee(__('topbar.menu_account'))
            ->assertSee(__('topbar.menu_preferences'))
            ->assertSee(__('topbar.menu_plan'))
            ->assertSee(__('topbar.menu_payments'))
            ->assertSee(__('topbar.menu_referrals'))
            ->assertSee(__('topbar.menu_api'))
            ->assertSee(__('topbar.menu_teams'))
            ->assertSee(__('Logout'));
    }

    public function test_sidebar_shows_stats_entries_only_with_website(): void
    {
        // 无网站：不渲染统计入口
        $user = $this->makeUser();
        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertDontSee(__('stats.nav.overview'));

        // 有网站：渲染统计组
        $this->makeWebsite($user, 'stats.test');
        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee(__('stats.nav.overview'))
            ->assertSee(__('stats.nav.realtime'))
            ->assertSee(__('stats.nav.visitors'))
            ->assertSee(__('stats.nav.heatmaps'))
            ->assertSee(__('stats.nav.replays'));
    }

    /**
     * Affiliate 插件门控（规格 §14.7：停用即关闭入口）
     * 设置以 'true'/'false' 字符串存储（saveSettings 约定），须归一化后判断：
     * 非空字符串 'false' 为 truthy，直接布尔判断会导致停用后仍可访问（回归）。
     */
    public function test_referrals_entries_hidden_and_404_when_affiliate_disabled(): void
    {
        $user = $this->makeUser();

        // 默认开启：侧边栏/顶部菜单可见，页面可访问
        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee(__('nav.referrals'))
            ->assertSee(__('topbar.menu_referrals'));
        $this->actingAs($user)->get('/referrals')->assertOk();

        // 停用：入口隐藏 + 页面 404
        Settings::set('affiliate.affiliate_is_enabled', 'false');
        try {
            $this->actingAs($user)->get('/dashboard')
                ->assertOk()
                ->assertDontSee(__('nav.referrals'))
                ->assertDontSee(__('topbar.menu_referrals'));

            $this->actingAs($user)->get('/referrals')->assertNotFound();
            $this->actingAs($user)->get('/referrals/withdrawals')->assertNotFound();
            $this->get('/affiliate')->assertNotFound();
        } finally {
            Settings::set('affiliate.affiliate_is_enabled', 'true');
        }
    }

    public function test_website_switch_stores_session_and_redirects(): void
    {
        $user = $this->makeUser();
        $site = $this->makeWebsite($user, 'switch.test');

        $response = $this->actingAs($user)->get('/website-switch/'.$site->website_id);

        $response->assertRedirect(route('dashboard', ['website_id' => $site->website_id]));
        $this->assertSame($site->website_id, session('current_website_id'));

        // 切换后侧边统计入口指向该网站
        $this->get('/dashboard')->assertOk()->assertSee('/stats/'.$site->website_id, false);
    }

    public function test_website_switch_rejects_foreign_website(): void
    {
        $owner = $this->makeUser();
        $site = $this->makeWebsite($owner, 'owner.test');
        $intruder = $this->makeUser(['email' => 'intruder@example.com']);

        $this->actingAs($intruder)->get('/website-switch/'.$site->website_id)->assertForbidden();
    }

    public function test_account_update_avatar_upload_and_remove(): void
    {
        $user = $this->makeUser();

        // 上传
        $this->actingAs($user)->put('/account', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => File::image('avatar.png', 64, 64),
        ])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertStringStartsWith('/uploads/avatars/user_'.$user->user_id.'_', $user->avatar);
        $this->assertFileExists(public_path(ltrim($user->avatar, '/')));

        // 原样保存（无头像字段）不清除头像
        $this->actingAs($user)->put('/account', [
            'name' => $user->name,
            'email' => $user->email,
        ])->assertRedirect();
        $this->assertNotNull($user->refresh()->avatar);

        // 移除
        $this->actingAs($user)->put('/account', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_remove' => '1',
        ])->assertRedirect();

        $this->assertNull($user->refresh()->avatar);
    }

    public function test_account_update_saves_anti_phishing_code_and_billing(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/account', [
            'name' => $user->name,
            'email' => $user->email,
            'anti_phishing_code' => 'MONIT-SAFE-2026',
            'billing_type' => 'business',
            'billing_name' => 'Monit Inc.',
            'billing_address' => '1 Web Street',
            'billing_city' => 'Shanghai',
            'billing_country' => 'CN',
            'billing_tax_id' => '91310000MA1FL0000X',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('MONIT-SAFE-2026', $user->anti_phishing_code);
        $this->assertSame('business', $user->billing['type'] ?? null);
        $this->assertSame('Monit Inc.', $user->billing['name'] ?? null);
        $this->assertSame('CN', $user->billing['country'] ?? null);

        // 账单表单（不带 anti_phishing_code 字段）不误清防钓鱼码
        $this->actingAs($user)->put('/account', [
            'name' => $user->name,
            'email' => $user->email,
            'billing_type' => 'personal',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('MONIT-SAFE-2026', $user->anti_phishing_code);
        $this->assertSame('personal', $user->billing['type'] ?? null);
    }

    public function test_account_page_renders_new_sections(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/account')
            ->assertOk()
            ->assertSee(__('account.avatar_label'))
            ->assertSee(__('account.anti_phishing_label'))
            ->assertSee(__('account.billing_title'))
            ->assertSee('enctype="multipart/form-data"', false);
    }
}
