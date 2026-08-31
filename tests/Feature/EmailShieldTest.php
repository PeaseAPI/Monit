<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\EmailShieldService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * M16 §14.9 Email Shield 插件：邮箱混淆输出防爬虫
 */
class EmailShieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_obfuscate_hides_plain_email_from_html(): void
    {
        $service = new EmailShieldService;
        $encoded = $service->obfuscate('contact@example.com');

        // 源码不含明文邮箱（爬虫无法直接提取）
        $this->assertStringNotContainsString('contact@example.com', $encoded);
        $this->assertStringNotContainsString('@', $encoded);

        // 浏览器解码后与原文一致
        $decoded = html_entity_decode($encoded, ENT_QUOTES | ENT_HTML5);
        $this->assertSame('contact@example.com', $decoded);
    }

    public function test_obfuscate_rejects_invalid_email(): void
    {
        $service = new EmailShieldService;

        $this->assertSame('not-an-email', html_entity_decode($service->obfuscate('not-an-email'), ENT_QUOTES | ENT_HTML5));
    }

    public function test_blade_directive_compiles_and_renders(): void
    {
        $rendered = Blade::render('@email_shield("hi@example.com")');

        $this->assertStringNotContainsString('hi@example.com', $rendered);
        $this->assertSame('hi@example.com', html_entity_decode($rendered, ENT_QUOTES | ENT_HTML5));
    }

    public function test_link_respects_plugin_toggle(): void
    {
        $service = new EmailShieldService;

        Setting::updateOrCreate(
            ['key' => 'plugins_email_shield_is_enabled'],
            ['value' => true]
        );
        $link = $service->link('hi@example.com');
        $this->assertStringContainsString('mailto:', $link);
        $this->assertStringNotContainsString('hi@example.com', $link);

        Setting::updateOrCreate(
            ['key' => 'plugins_email_shield_is_enabled'],
            ['value' => false]
        );
        $this->assertSame('hi@example.com', $service->link('hi@example.com'));
    }
}
