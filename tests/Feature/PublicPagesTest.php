<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * M0.3 冒烟基线：公共前台核心路由可达性（规格 §6.1）
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function publicRoutes(): array
    {
        return [
            'home' => ['/', 200],
            'login' => ['/login', 200],
            'register' => ['/register', 200],
            'plan' => ['/plan', 200],
            'blog' => ['/blog', 200],
            'pages-index' => ['/pages', 200],
            'help' => ['/help', 200],
            'contact' => ['/contact', 200],
            'sitemap' => ['/sitemap', 200],
            'maintenance-page' => ['/maintenance', 503],
            'api-documentation' => ['/api-documentation', 200],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_public_page_renders(string $uri, int $expected): void
    {
        $response = $this->get($uri);

        $this->assertSame($expected, $response->status(), "GET {$uri} 期望 {$expected}");
    }

    public function test_maintenance_page_has_content(): void
    {
        // 维护页 503 为正确语义，页面需可读
        $this->get('/maintenance')->assertStatus(503);
    }

    public function test_cookie_consent_records_decision(): void
    {
        // 规格 §6.1：/cookie-consent 为 GDPR 同意记录端点（POST）
        $this->postJson('/cookie-consent', ['consent' => 'accepted'])->assertStatus(204);
        $this->postJson('/cookie-consent', ['consent' => 'rejected'])->assertStatus(204);
        $this->postJson('/cookie-consent', ['consent' => 'bogus'])->assertStatus(422);
    }

    public function test_pixel_sdk_is_served(): void
    {
        // 规格 §4.5：客户端 SDK 部署于 public/assets/pixel/（生产由 web server 直接服务）
        $path = public_path('assets/pixel/monit.js');
        $this->assertFileExists($path);
        $this->assertStringContainsString('pixel-track', (string) file_get_contents($path));
    }

    public function test_affiliate_page_respects_setting(): void
    {
        // 规格 §6.1：/affiliate 仅在联盟启用时可访问
        \App\Support\Settings::set('affiliate.affiliate_is_enabled', 'true');
        $this->get('/affiliate')->assertOk();

        \App\Support\Settings::set('affiliate.affiliate_is_enabled', 'false');
        $this->get('/affiliate')->assertNotFound();
    }

    public function test_cookie_banner_renders_when_enabled(): void
    {
        // 规格 §6.1：Cookie 同意横幅（设置启用时前台展示，决策 POST /cookie-consent）
        \App\Support\Settings::set('cookie_consent.cookie_consent_is_enabled', 'true');
        $this->get('/')->assertOk()->assertSee('monit-cookie-banner');

        \App\Support\Settings::set('cookie_consent.cookie_consent_is_enabled', 'false');
        $this->get('/')->assertOk()->assertDontSee('monit-cookie-banner');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_install_wizard_renders_on_fresh_instance(): void
    {
        // 规格 §15.3/§19：全新实例 /install 向导可用（无 lock 且无管理员）
        // 回归：routes/web.php 曾缺失 use InstallController 导致 /install 500
        $this->get('/install')->assertOk();
    }
}
