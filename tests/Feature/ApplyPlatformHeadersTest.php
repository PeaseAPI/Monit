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
}
