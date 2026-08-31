<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Website;
use App\Support\Brand;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M23 交付验证（规格书 §15 品牌可控 / 模板机制 / 多语言 / 部署与文档）
 * - 品牌设置组：logo/favicon/ICP/页脚代码/主题色/Hero 覆盖
 * - 模板机制：themes/{theme} 解析 + 回退 default
 * - 多语言：zh_TW/ru/be/ms 完整键集与 en 一致且 JSON 有效
 * - 静态文档页：/docs/*.html 200
 * - 跟踪优化：pixel 路由无 Session Cookie、204 响应、Website 缓存失效
 */
class M23Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'M23', 'email' => 'm23@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1],
        ]);
    }

    /** 语言文件完整性：6 种语言键集与 en 完全一致且可解析 */
    public function test_language_files_complete(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);

        foreach (['zh_CN', 'zh_TW', 'ru', 'be', 'ms'] as $locale) {
            $path = lang_path($locale.'.json');
            $this->assertFileExists($path, $locale.'.json 缺失');

            $data = json_decode(file_get_contents($path), true);
            $this->assertNotNull($data, $locale.'.json 解析失败');
            $this->assertSame(array_keys($en), array_keys($data), $locale.' 键集与 en 不一致');
            $this->assertNotEmpty($data['landing.hero_title'], $locale.' landing.hero_title 为空');
        }
    }

    /** 默认落地页渲染：主题视图 + 语言键 + 品牌名 */
    public function test_landing_renders_default_theme(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('landing.hero_title'), false)
            ->assertSee(__('landing.faq_title'), false)
            ->assertSee(Brand::name());
    }

    /** 品牌覆盖：站点名 / Hero 标题 / ICP / 页脚代码 全部后台可控 */
    public function test_branding_overrides_render_on_landing(): void
    {
        Setting::updateOrCreate(['key' => 'branding.site_name'], ['value' => 'AcmeStats']);
        Setting::updateOrCreate(['key' => 'branding.logo_url'], ['value' => 'https://cdn.example.com/logo.png']);
        Setting::updateOrCreate(['key' => 'branding.landing_hero_title'], ['value' => 'Acme Hero Title']);
        Setting::updateOrCreate(['key' => 'branding.footer_icp'], ['value' => '京ICP备2026000000号-1']);
        Setting::updateOrCreate(['key' => 'branding.footer_custom_html'], ['value' => '<div id="acme-footer">acme-custom-code</div>']);
        Settings::flush();

        $this->get('/')
            ->assertOk()
            ->assertSee('AcmeStats')
            ->assertSee('https://cdn.example.com/logo.png')
            ->assertSee('Acme Hero Title')
            ->assertSee('京ICP备2026000000号-1')
            ->assertSee('https://beian.miit.gov.cn/', false)
            ->assertSee('acme-custom-code', false);
    }

    /** 模板机制：不存在的主题回退 default */
    public function test_theme_fallback_mechanism(): void
    {
        Setting::updateOrCreate(['key' => 'branding.landing_theme'], ['value' => 'not_exists_theme']);
        Settings::flush();

        $this->get('/')->assertOk()->assertSee(__('landing.features_title'), false);
        $this->assertSame('not_exists_theme', Brand::landingTheme());
    }

    /** 品牌主色运行时覆盖：非默认色生成 style 覆盖，默认色不生成 */
    public function test_brand_primary_color_overrides(): void
    {
        $this->assertSame('', Brand::colorStyleTag());

        Setting::updateOrCreate(['key' => 'branding.primary_color'], ['value' => '#e11d48']);
        Settings::flush();

        $tag = Brand::colorStyleTag();
        $this->assertStringContainsString('--color-brand-600', $tag);
        $this->assertStringContainsString('#', $tag);
    }

    /** Brand 回退链：logo → custom_images.logo 兼容 */
    public function test_brand_logo_fallback_chain(): void
    {
        $this->assertNull(Brand::logoUrl());

        Setting::updateOrCreate(['key' => 'custom_images.logo'], ['value' => 'https://legacy.example.com/logo.png']);
        Settings::flush();
        $this->assertSame('https://legacy.example.com/logo.png', Brand::logoUrl());

        Setting::updateOrCreate(['key' => 'branding.logo_dark_url'], ['value' => 'https://cdn.example.com/dark.png']);
        Settings::flush();
        $this->assertSame('https://cdn.example.com/dark.png', Brand::logoUrl(true));
    }

    /** 静态文档页：产品/安装/使用 3 个 HTML 可直接访问 */
    public function test_static_docs_pages_accessible(): void
    {
        foreach (['index', 'install', 'usage'] as $page) {
            $this->get("/docs/{$page}.html")->assertOk();
        }
    }

    /** 跟踪优化：pixel 端点 204 + 无 Session Cookie（无中间件路由组） */
    public function test_pixel_endpoint_has_no_session_overhead(): void
    {
        Website::create([
            'user_id' => User::first()->user_id,
            'pixel_key' => 'px_m23', 'name' => 'M23 Site',
            'scheme' => 'https', 'host' => 'm23.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $response = $this->post('/pixel-track/px_m23', [
            'data' => json_encode(['type' => 'pageview', 'url' => 'https://m23.test/', 'data' => []]),
        ]);

        $response->assertStatus(204);
        $this->assertEmpty($response->headers->getCookies(), 'pixel 路由不应启动 Session/Cookie');
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    /** Website 查询缓存失效钩子：更新/删除站点后缓存被清除 */
    public function test_website_cache_invalidated_on_save(): void
    {
        $website = Website::create([
            'user_id' => User::first()->user_id,
            'pixel_key' => 'px_cache', 'name' => 'Cache Site',
            'scheme' => 'https', 'host' => 'cache.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        cache()->put('pixel.website.px_cache', 'stale-value', 60);
        $website->update(['name' => 'Cache Site v2']);
        $this->assertNull(cache()->get('pixel.website.px_cache'));

        cache()->put('pixel.website.px_cache', 'stale-again', 60);
        $website->delete();
        $this->assertNull(cache()->get('pixel.website.px_cache'));
    }
}
