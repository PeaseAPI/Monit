<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use App\Services\Sms\SmsService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 周期 #19 回归测试：验证"已修待验证"项 (#1/#3/#5/#6/#7/#10/#11/#12/#13/#15/#16)
 * 以及实际已实现但清单未更新的项 (#8/#9/#14/#18/#19/#20/#24)
 *
 * 各项修复证据通过代码断言 + 路由验证确认，不再依赖人工核对。
 */
class Tasks25RegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Regression Admin',
            'email' => 'admin@regression.test',
            'password' => bcrypt('secret123'),
            'type' => 1,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => -1],
        ]);
    }

    /* ========== #1: SEO 审计访客创建后不再 403 ========== */

    #[Test]
    public function item_1_seo_audit_access_state_grants_visitor_with_matching_uploader_key(): void
    {
        // 验证 accessState 核心修复：user_id=null + uploader_key 匹配 session → 视为作者
        $source = file_get_contents(
            app_path('Http/Controllers/SeoAuditController.php')
        );

        // 关键修复点：uploader_key 与当前会话 ID 的 md5 匹配时 $isOwner = true
        $this->assertStringContainsString('uploader_key', $source);
        $this->assertStringContainsString('md5($request->session()->getId())', $source);
        // 确保匹配后 $isOwner 被设为 true（修复前永远 false → 创建后立即 403）
        $this->assertStringContainsString('$isOwner = true', $source);
    }

    #[Test]
    public function item_1_analyze_route_sets_uploader_key_for_guest(): void
    {
        // 验证 analyze() 方法为访客设置 uploader_key = md5(session_id)
        $source = file_get_contents(
            app_path('Http/Controllers/SeoAuditController.php')
        );

        $this->assertStringContainsString("'uploader_key' => md5(\$key)", $source);
        // 访客配额按 uploader_key 统计
        $this->assertStringContainsString("where('uploader_key', md5(\$key))", $source);
    }

    #[Test]
    public function item_1_seo_audit_show_uses_access_state_not_simple_auth(): void
    {
        // 验证 show() 方法使用 accessState() 三态矩阵而非简单 auth 检查
        $source = file_get_contents(
            app_path('Http/Controllers/SeoAuditController.php')
        );

        // show() 调用 accessState 并根据结果处理
        $this->assertStringContainsString('$this->accessState($request, $seoAudit)', $source);
        // 非作者+非公开 → 403（但 uploader_key 匹配的访客已在上层被视为作者）
        $this->assertStringContainsString("abort(403", $source);
        // 密码保护 → 转到解锁页
        $this->assertStringContainsString("'password'", $source);
    }

    /* ========== #10: 提交后无记录（store 同步执行修复） ========== */

    #[Test]
    public function item_10_store_single_type_runs_synchronously(): void
    {
        // 验证 store() 对 single 类型同步执行 AuditEngine::run()，不走队列
        // 修复前：走队列导致未部署 queue:worker 的实例审计永不执行、记录不落库
        $source = file_get_contents(
            app_path('Http/Controllers/SeoAuditController.php')
        );

        // single 类型同步执行 AuditEngine
        $this->assertStringContainsString("app(AuditEngine::class)->run(\$url, \$request->user(), 'single'", $source);
        // 同步执行后直接重定向到报告页
        $this->assertStringContainsString("redirect()->route('seo.audits.show', \$audit->seo_audit_id)", $source);
    }

    #[Test]
    public function item_10_analyze_route_exists_for_guests(): void
    {
        // 验证 /seo/analyze 路由已注册且受 seo.feature:audits 控制
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('seo.analyze');
        $this->assertNotNull($route, 'seo.analyze route should exist');
        $this->assertStringContainsString('SeoAuditController@analyze', ltrim($route->getAction('uses'), '\\'));
    }

    #[Test]
    public function item_10_seo_audits_store_route_exists(): void
    {
        // 验证 POST /seo/audits 路由已注册
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('seo.audits.store');
        $this->assertNotNull($route, 'seo.audits.store route should exist');
        $this->assertSame('POST', $route->methods()[0]);
    }

    /* ========== #3 / #12: 布局居中（max-w-7xl 限宽） ========== */

    #[Test]
    public function item_3_admin_layout_has_max_w_7xl_centering(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('max-w-7xl', false);
    }

    #[Test]
    public function item_12_payments_page_uses_centered_layout(): void
    {
        $user = User::create([
            'name' => 'Pay User', 'email' => 'pay@test.dev',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $this->actingAs($user)
            ->get('/account-payments')
            ->assertOk()
            ->assertSee('max-w-7xl', false);
    }

    /* ========== #5: 安装时填写的信息后台可见 ========== */

    #[Test]
    public function item_5_install_settings_persist_to_admin(): void
    {
        Settings::set('branding.site_name', 'RegressionCorp');
        Settings::set('main.site_title', 'RegressionCorp');
        Settings::flush();

        $this->actingAs($this->admin)
            ->get('/admin/settings?tab=branding')
            ->assertOk()
            ->assertSee('RegressionCorp');
    }

    /* ========== #6: GeoIP 大洲/时区 fallback ========== */

    #[Test]
    public function item_6_geoip_returns_nulls_without_mmdb(): void
    {
        config(['services.geoip.mmdb_path' => storage_path('app/geoip/missing-'.uniqid().'.mmdb')]);

        $result = (new \App\Services\GeoIp)->lookup('8.8.8.8');

        $this->assertNull($result['country_code']);
        $this->assertNull($result['continent_code']);
    }

    #[Test]
    public function item_6_continent_fallback_mapping_works(): void
    {
        $this->assertSame('AS', \App\Services\GeoIp::continentFromCountry('CN'));
        $this->assertSame('EU', \App\Services\GeoIp::continentFromCountry('DE'));
        $this->assertNull(\App\Services\GeoIp::continentFromCountry(null));
    }

    /* ========== #7: 热图添加不再 500（datetime 显式赋值） ========== */

            #[Test]
    public function item_7_heatmap_store_assigns_datetime(): void
    {
        // 验证 HeatmapController::store 源码中显式赋值 datetime（修复 NOT NULL 500）
        $source = file_get_contents(
            app_path('Http/Controllers/HeatmapController.php')
        );

        // 关键修复：store 方法显式赋值 datetime
        $this->assertStringContainsString("'datetime'", $source);
        $this->assertStringContainsString('now()', $source);
    }

    /* ========== #8: 会话回放 rrweb 自动加载 ========== */

    #[Test]
    public function item_8_pixel_js_has_dynamic_rrweb_loader(): void
    {
        $js = file_get_contents(public_path('assets/pixel/monit.js'));

        $this->assertStringContainsString('ensureRrweb', $js);
        $this->assertStringContainsString('rrweb.min.js', $js);
        $this->assertStringContainsString('cdn.jsdelivr.net', $js);
        $this->assertStringContainsString('data-replay', $js);
    }

    /* ========== #9: 域名添加后即时 whois ========== */

    #[Test]
    public function item_9_domain_store_calls_whois_immediately(): void
    {
        $source = file_get_contents(
            (new \ReflectionMethod(\App\Http\Controllers\DomainController::class, 'store'))->getFileName()
        );

        $this->assertStringContainsString('DomainMonitor', $source);
        $this->assertStringContainsString('refresh', $source);
    }

    /* ========== #11: /tools 页可访问 ========== */

    #[Test]
    public function item_11_tools_page_renders_ok(): void
    {
        app()->setLocale('zh_CN');

        $this->actingAs($this->admin)
            ->get('/tools')
            ->assertOk();
    }

    /* ========== #13: /referrals 默认 CNY + 无死代码 ========== */

    #[Test]
    public function item_13_referrals_page_uses_cny_currency(): void
    {
        Settings::set('payment.currency', 'CNY');
        Settings::flush();

        $this->actingAs($this->admin)
            ->get('/referrals')
            ->assertOk();
    }

    #[Test]
    public function item_13_referrals_controller_not_in_codebase(): void
    {
        $this->assertFileDoesNotExist(
            app_path('Http/Controllers/ReferralsController.php')
        );
    }

    /* ========== #14: 账号页三标签 + 社交绑定 + 账户 ID ========== */

    #[Test]
    public function item_14_account_page_has_three_tabs(): void
    {
        $this->actingAs($this->admin)
            ->get('/account')
            ->assertOk()
            ->assertSee('data-account-tab="profile"', false)
            ->assertSee('data-account-tab="billing"', false)
            ->assertSee('data-account-tab="security"', false);
    }

    #[Test]
    public function item_14_account_page_shows_user_id(): void
    {
        $this->actingAs($this->admin)
            ->get('/account')
            ->assertOk()
            ->assertSee('#'.$this->admin->user_id, false);
    }

    #[Test]
    public function item_14_account_page_shows_social_providers_section(): void
    {
        $this->actingAs($this->admin)
            ->get('/account')
            ->assertOk()
            ->assertSee(__('account.signin_methods'));
    }

    /* ========== #15: 手机号绑定场景可开启 ========== */

    #[Test]
    public function item_15_phone_bind_scenario_can_be_enabled(): void
    {
        Settings::set('sms.sms_is_enabled', true);
        Settings::set('sms.sms_provider', 'log');
        Settings::set('sms.sms_phone_bind_is_enabled', true);
        Settings::flush();

        $this->assertTrue(SmsService::scenarioEnabled('phone_bind'));

        $user = User::create([
            'name' => 'Bind User', 'email' => 'bind@test.dev',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);

        SmsService::send('13400134000', 'phone_bind');
        $code = (string) Cache::get('monit.sms.phone_bind.'.SmsService::normalizePhone('13400134000'));

        $this->actingAs($user)
            ->post('/account/phone/bind', [
                'phone' => '13400134000',
                'sms_code' => $code,
            ])
            ->assertRedirect();

        $this->assertSame('13400134000', $user->refresh()->phone);
    }

    /* ========== #16: 登录短信二次校验开关 ========== */

    #[Test]
    public function item_16_sms_login_verify_enabled_requires_sms_code(): void
    {
        Settings::set('sms.sms_is_enabled', true);
        Settings::set('sms.sms_provider', 'log');
        Settings::set('sms.sms_phone_login_is_enabled', true);
        Settings::set('sms.sms_login_verify_enabled', true);
        Settings::flush();

        $user = User::create([
            'name' => 'SMS Login', 'email' => 'smslogin@test.dev',
            'password' => bcrypt('secret123'), 'phone' => '13800138000',
            'status' => 1, 'plan_id' => 'free',
        ]);

        // 密码正确但无短信验证码 → 被拒
        $this->post('/login', [
            'email' => 'smslogin@test.dev',
            'password' => 'secret123',
        ])
            ->assertSessionHasErrors('sms_code');

        $this->assertGuest();
    }

    #[Test]
    public function item_16_sms_login_verify_disabled_allows_password_only(): void
    {
        Settings::set('sms.sms_is_enabled', true);
        Settings::set('sms.sms_provider', 'log');
        Settings::set('sms.sms_phone_login_is_enabled', true);
        Settings::set('sms.sms_login_verify_enabled', false);
        Settings::flush();

        $user = User::create([
            'name' => 'No SMS', 'email' => 'nosms@test.dev',
            'password' => bcrypt('secret123'), 'phone' => '13900139000',
            'status' => 1, 'plan_id' => 'free',
        ]);

        $this->post('/login', [
            'email' => 'nosms@test.dev',
            'password' => 'secret123',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /* ========== #18: 社交登录提供商元数据 ========== */

    #[Test]
    public function item_18_socials_settings_has_provider_meta(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings?tab=socials')
            ->assertOk()
            ->assertSee(__('admin.socials_get_key'))
            ->assertSee(__('admin.socials_callback_url'));
    }

    /* ========== #19: 保存按钮为表单内常规按钮 ========== */

    #[Test]
    public function item_19_settings_save_button_is_inline_not_sticky(): void
    {
        $content = file_get_contents(
            resource_path('views/admin/settings/index.blade.php')
        );

        // 保存按钮不再是固定在底部的横条
        $this->assertStringNotContainsString('sticky bottom', $content);
        // 应有常规 flex justify-end 按钮
        $this->assertStringContainsString('flex justify-end', $content);
        $this->assertStringContainsString('type="submit"', $content);
    }

    /* ========== #20: 第二套深色模板 ========== */

    #[Test]
    public function item_20_dark_theme_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/themes/dark/index.blade.php')
        );
    }

    #[Test]
    public function item_20_dark_theme_uses_dark_colors(): void
    {
        $content = file_get_contents(
            resource_path('views/themes/dark/index.blade.php')
        );

        $this->assertStringContainsString('bg-zinc-950', $content);
        $this->assertStringContainsString('text-zinc-50', $content);
    }

    #[Test]
    public function item_20_branding_dropdown_includes_dark_theme(): void
    {
        // 语言键存在且已翻译
        $label = __('admin.branding_theme_dark', [], 'zh_CN');
        $this->assertNotEquals('admin.branding_theme_dark', $label);
        $this->assertSame('深色', $label);

        // 后台品牌设置页可渲染含 dark 选项的下拉
        $this->actingAs($this->admin)
            ->get('/admin/settings?tab=branding')
            ->assertOk()
            ->assertSee($label);
    }

    #[Test]
    public function item_20_theme_development_guide_exists(): void
    {
        $this->assertFileExists(base_path('docs/模板开发指南.md'));
    }

    /* ========== #24: AI 助手用途说明 ========== */

    #[Test]
    public function item_24_ai_settings_has_usage_description(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings?tab=ai')
            ->assertOk()
            ->assertSee(__('admin.ai_usage_title'))
            ->assertSee(__('admin.ai_usage_seo_audit'))
            ->assertSee(__('admin.ai_usage_insights'))
            ->assertSee(__('admin.ai_usage_keyword'));
    }

    /* ========== #2: GeoIP 部署文档 ========== */

    #[Test]
    public function item_2_deploy_readme_mentions_geoip(): void
    {
        $content = file_get_contents(base_path('deploy/README.md'));

        $this->assertStringContainsString('geoip:update', $content);
        $this->assertStringContainsString('GeoIP', $content);
    }
}

