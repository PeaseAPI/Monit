<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 公开面 SEO 获客闭环（对标 monit.cn）
 * - 落地页导航/页脚/hero 暴露 免费工具 + 审计目录 + 即时分析入口（受后台开关控制）
 * - SEO 公开页使用 layouts.public：顶部导航可往返首页，不再是无导航孤岛
 */
class SeoPublicNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 访客入口可见性前提：工具中心对访客开放（与路由 SeoGuestAccess 一致）
        Settings::set('seo.tools_guest_access', 'true');
    }

    public function test_landing_links_to_seo_public_pages(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee(route('seo.tools'), false)
            ->assertSee(route('seo.directory'), false)
            // hero 免费 SEO 分析表单
            ->assertSee('action="'.route('seo.analyze').'"', false)
            ->assertSee(__('landing.nav_seo_tools'))
            ->assertSee(__('landing.nav_seo_directory'));
    }

    public function test_landing_hides_seo_entries_when_disabled(): void
    {
        Settings::set('seo.tools_is_enabled', false);
        Settings::set('seo.audits_is_enabled', false);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee(route('seo.tools'), false)
            ->assertDontSee(route('seo.directory'), false)
            ->assertDontSee(route('seo.analyze'), false);
    }

    public function test_landing_hides_tools_link_for_guests_when_guest_access_off(): void
    {
        Settings::set('seo.tools_guest_access', false);

        $this->get('/')
            ->assertOk()
            ->assertDontSee(route('seo.tools'), false)
            // 审计目录不受访客开关影响
            ->assertSee(route('seo.directory'), false);
    }

    public function test_tools_page_uses_public_layout_with_navigation(): void
    {
        $response = $this->get(route('seo.tools'));

        $response->assertOk()
            ->assertSee(__('seo.tools_title'))
            // 顶部导航：可返回首页 + 前往审计目录 + 登录入口
            ->assertSee('href="'.route('index').'"', false)
            ->assertSee('href="'.route('seo.directory').'"', false)
            ->assertSee('href="'.route('login').'"', false)
            // 工具卡片（word_counter 为注册表内置工具）
            ->assertSee(route('seo.tools.show', 'word_counter'), false);
    }

    public function test_directory_page_uses_public_layout_with_navigation(): void
    {
        $this->get(route('seo.directory'))
            ->assertOk()
            ->assertSee(__('seo.directory_title'))
            ->assertSee('href="'.route('index').'"', false)
            ->assertSee('href="'.route('seo.tools').'"', false);
    }

    public function test_tool_page_form_submits_and_renders_result(): void
    {
        // 工具页表单（word_counter：textarea input[text]）
        $this->get(route('seo.tools.show', 'word_counter'))
            ->assertOk()
            ->assertSee('action="'.route('seo.tools.process', 'word_counter').'"', false)
            ->assertSee('name="input[text]"', false);

        // 执行工具：重定向回显结果 + 用量记录（访客 uploader_key；back() 依赖 Referer 落回工具页）
        $response = $this->withHeaders(['Referer' => route('seo.tools.show', 'word_counter')])
            ->followingRedirects()
            ->post(route('seo.tools.process', 'word_counter'), [
                'input' => ['text' => 'hello seo world'],
            ]);

        $response->assertOk()->assertSee(__('seo.result'));

        $this->assertDatabaseHas('seo_tool_uses', [
            'tool' => 'word_counter',
            'user_id' => null,
        ]);
    }
}
