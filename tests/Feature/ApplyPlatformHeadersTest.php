<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 第十三轮：平台响应安全头基线（ApplyPlatformHeaders，web 组全局）
 */
class ApplyPlatformHeadersTest extends TestCase
{
    use RefreshDatabase;

    /** nosniff 无条件输出（MIME 嗅探禁用，零破坏基线） */
    public function test_nosniff_always_present(): void
    {
        $this->get('/login')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** iframe 开关关闭 → X-Frame-Options: DENY（防点击劫持） */
    public function test_frame_options_deny_when_iframe_disabled(): void
    {
        Settings::set('main.iframe_is_enabled', 'false');

        $this->get('/login')->assertHeader('X-Frame-Options', 'DENY');
    }

    /** iframe 开关显式开启 → 不输出 X-Frame-Options（保持原版可嵌入语义） */
    public function test_frame_options_absent_when_iframe_allowed(): void
    {
        // 显式设 true：Settings::set 写缓存跨测试残留（array cache），依赖
        // 「未设置」语义会被前一个用例的 false 污染
        Settings::set('main.iframe_is_enabled', 'true');

        $this->get('/login')->assertHeaderMissing('X-Frame-Options');
    }

    /** 第十八轮：X-Powered-By 必须被移除（PHP 版本指纹防护） */
    public function test_powered_by_header_removed(): void
    {
        $this->get('/login')->assertHeaderMissing('X-Powered-By');
    }

    /** 第十八轮：未配置 referrer_policy 时输出安全默认值 */
    public function test_referrer_policy_defaults_to_strict_origin_when_cross_origin(): void
    {
        $this->get('/login')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /** 第十八轮：管理员显式配置的 referrer_policy 优先于默认值 */
    public function test_referrer_policy_explicit_setting_wins(): void
    {
        Settings::set('main.referrer_policy', 'no-referrer');

        $this->get('/login')->assertHeader('Referrer-Policy', 'no-referrer');
    }
}
