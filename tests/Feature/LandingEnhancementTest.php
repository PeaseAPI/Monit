<?php

namespace Tests\Feature;

use App\Models\LightweightEvent;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 任务 D：落地页增强（语言切换 / 统计徽章 / 月年切换 / 套餐明细 / 评价区）
 * + 生产配置导入（ProductionSeeder，数据源 www_monit_cn.sql）
 */
class LandingEnhancementTest extends TestCase
{
    use RefreshDatabase;

    /** 造 1 个站点 + 2 条轻量事件，用于统计徽章断言 */
    private function seedStatsFixture(): void
    {
        $user = User::create([
            'name' => 'S', 'email' => 'stats@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom', 'plan_settings' => [],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_stats',
            'name' => 'StatsSite', 'scheme' => 'https', 'host' => 'stats.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        foreach ([0, 1] as $i) {
            LightweightEvent::create([
                'website_id' => $website->website_id, 'type' => 'pageview',
                'path' => '/', 'date' => now(),
            ]);
        }
    }

    public function test_locale_switch_persists_across_requests(): void
    {
        // 白名单语言：写入 session，中间件 setLocale 生效
        $this->get('/locale/en')->assertRedirect();
        $response = $this->get('/');
        $response->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('Trusted by growing teams');

        // 切回中文
        $this->get('/locale/zh_CN')->assertRedirect();
        $this->get('/')->assertOk()->assertSee('lang="zh-CN"', false);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->get('/locale/fr_X')->assertRedirect();
        $this->get('/')->assertOk()->assertSee('lang="zh-CN"', false);
    }

    public function test_landing_shows_stats_badge(): void
    {
        $this->seedStatsFixture();

        $this->get('/')->assertOk()
            ->assertSee(__('landing.stats_websites'))
            ->assertSee(__('landing.stats_pageviews'))
            ->assertSee('px', false) // data-price 属性同时存在（定价切换）不影响本断言
            ->assertSee('2'); // 2 条 pageview（number_format(2) = "2"）
    }

    public function test_landing_shows_billing_toggle_and_feature_list(): void
    {
        Plan::create([
            'plan_id' => 't1', 'name' => 'T1', 'description' => 'd',
            'prices' => ['CNY' => ['monthly' => 10, 'annual' => 100]],
            'settings' => ['websites_limit' => 5, 'sessions_replays_limit' => 0,
                'events_children_retention' => 90, 'api_is_enabled' => true],
            'order' => 1, 'trial_days' => 7, 'is_enabled' => true,
        ]);

        $this->get('/')->assertOk()
            ->assertSee('billing-toggle', false)
            ->assertSee(__('landing.billing_monthly'))
            ->assertSee(__('landing.billing_annual'))
            ->assertSee('data-price="annual"', false)
            ->assertSee(__('landing.feat_websites', ['count' => 5]))
            ->assertSee(__('landing.feat_no_replays'))
            ->assertSee(__('landing.trial_days_note', ['days' => 7]));
    }

    public function test_landing_shows_testimonials(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(__('landing.testimonials_title'))
            ->assertSee(__('landing.testimonial_1_author'));
    }

    public function test_production_seeder_imports_real_config(): void
    {
        $this->seed(ProductionSeeder::class);

        // 套餐：三档 + CNY/USD 双币直配价
        $plus = Plan::find('plus');
        $this->assertSame(9.0, (float) $plus->prices['CNY']['monthly']);
        $this->assertSame(99.0, (float) $plus->prices['CNY']['annual']);
        $this->assertSame(1.9, (float) $plus->prices['USD']['monthly']);
        $this->assertSame(7, $plus->trial_days);
        $this->assertSame(5, $plus->settings['websites_limit']);
        $this->assertSame([1, 2], $plus->taxes_ids);

        $this->assertNotNull(Plan::find('pro'));
        $this->assertNotNull(Plan::find('ultra'));
        $this->assertSame(-1, Plan::find('ultra')->settings['websites_limit']);

        // 税费：6% inclusive CN
        $this->assertDatabaseCount('taxes', 2);
        $this->assertDatabaseHas('taxes', ['name' => '技术服务费', 'value' => 6, 'type' => 'inclusive']);

        // 品牌：ICP 备案生产值
        $this->assertSame('冀ICP备18013359号-38', Setting::where('key', 'branding.footer_icp')->value('value'));
    }
}
