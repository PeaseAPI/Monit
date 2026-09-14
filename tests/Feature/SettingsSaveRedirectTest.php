<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Database\Seeders\HelpCenterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 周期 #26 回归：设置保存被 302 到帮助文章页（用户实测 bug）。
 *
 * 根因：AdminSettings::update() 用 back()，框架 back() 优先读 session 的
 * url.previous；每个站内 GET（含设置页新标签打开的「详细申请教程 →」帮助文章，
 * target=_blank 同 session）都会经 StartSession 刷新它——用户点开教程后回到
 * 设置页填 key 保存，被 302 到 serpapi-key 文章页（数据其实已保存）。
 * 修复：update/clearCache 全部显式 redirect 回 admin.settings.index?tab=...，
 * 校验失败路径改手动 Validator（ValidationException 渲染同样依赖 previous()）。
 */
class SettingsSaveRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => '设置回跳管理员', 'email' => uniqid('setredir-').'@admin.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 1,
        ]);

        // 帮助文章存于 DB（模拟新标签打开「详细申请教程」目标页）
        $this->seed(HelpCenterSeeder::class);

        // Settings 有进程内静态缓存，跨测试会残留上一用例读到的值
        Settings::flush();
    }

    #[Test]
    public function save_after_opening_help_doc_in_new_tab_stays_on_settings(): void
    {
        // 1. 打开设置页（session previousUrl 记录为设置页）
        $this->actingAs($this->admin)
            ->get('/admin/settings?tab=seo')
            ->assertOk();

        // 2. 同 session 新标签页打开帮助文章（previousUrl 被刷新为文章页）
        $this->get('/help/article/serpapi-key')->assertOk();

        // 3. 回到设置页填 key 点保存 → 必须回到设置页，而不是被 302 到文章页
        $response = $this->put('/admin/settings', [
            'group' => 'seo',
            'serpapi_api_key' => 'sk-test-serpapi-123',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'seo']));
        $this->assertStringNotContainsString('help/article', (string) $response->headers->get('Location'));

        // 数据实际落库（此前用户误以为保存失败）
        $this->assertSame('sk-test-serpapi-123', Settings::get('seo.serpapi_api_key'));

        // 回跳后的设置页可正常渲染且携带成功提示
        $this->get(route('admin.settings.index', ['tab' => 'seo']))
            ->assertOk()
            ->assertSee(__('msg.settings_saved', ['group' => 'seo']));
    }

    #[Test]
    public function validation_failure_returns_to_settings_tab_not_previous_url(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings?tab=seo')
            ->assertOk();

        // 模拟先访问过其他站内页（previousUrl 污染源）
        $this->get('/help')->assertOk();

        // serpapi_api_key 超过 256 上限 → 校验失败也必须回设置页
        $response = $this->put('/admin/settings', [
            'group' => 'seo',
            'serpapi_api_key' => str_repeat('x', 300),
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'seo']));
        $response->assertSessionHasErrors(['serpapi_api_key']);
        $this->assertNull(Settings::get('seo.serpapi_api_key'));
    }

    #[Test]
    public function back_endpoints_fall_back_to_referer_after_new_tab_pollution(): void
    {
        // 其余仍使用 back() 的控制器由全局兜底中间件保护:
        // POST 时把被污染的 session previousUrl 对齐为 Referer(表单所在页)
        Route::middleware('web')->post('/_test-back-endpoint', fn () => back());

        $this->actingAs($this->admin)
            ->get('/admin/settings?tab=seo')
            ->assertOk();

        // 新标签打开帮助文章 → previousUrl 被污染为文章页
        $this->get('/help/article/serpapi-key')->assertOk();

        // POST 提交(浏览器带 Referer=表单页)→ back() 必须回表单页而非文章页
        $response = $this->from(route('help.article', 'serpapi-key'))
            ->post('/_test-back-endpoint', [], [
                'Referer' => url('/admin/settings?tab=seo'),
            ]);

        $response->assertRedirect(url('/admin/settings?tab=seo'));
        $this->assertStringNotContainsString('help/article', (string) $response->headers->get('Location'));
    }

    #[Test]
    public function all_settings_panels_have_top_level_sections_matched_with_source(): void
    {
        // 设置页 3.0：URL 驱动单 tab 渲染——逐 tab GET 渲染,对每个 panel 断言:
        // 顶层 section 数 == partial 源码 <section 开标签数(嵌套错位即失败)
        // main 拆分后共 35 个 tab(原 33 + main_features + main_display)
        $readonlyTabs = ['cache', 'health', 'support'];
        $mismatches = [];

        foreach (['main', 'main_features', 'main_display', 'users', 'content', 'analytics', 'seo', 'maps', 'tickets', 'branding', 'custom', 'custom_images', 'ads', 'cookie_consent', 'socials', 'announcements', 'payment', 'payment_gateways', 'business', 'plan_free', 'plan_guest', 'plan_custom', 'affiliate', 'smtp', 'sms', 'ai', 'captcha', 'email_notifications', 'internal_notifications', 'webhooks', 'offload', 'cron', 'cache', 'health', 'support'] as $tab) {
            $html = (string) $this->actingAs($this->admin)
                ->get('/admin/settings?tab='.$tab)
                ->assertOk()
                ->getContent();

            $dom = new \DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);

            $partial = resource_path("views/admin/settings/partials/{$tab}.blade.php");
            $source = (string) file_get_contents($partial);
            $expected = preg_match_all('/<section\b/i', $source);

            // 常规 panel: form > div(partial外层) > section;只读 panel: div(partial外层) > section
            $query = in_array($tab, $readonlyTabs, true)
                ? "//div[@id=\"panel-{$tab}\"]/div/section"
                : "//div[@id=\"panel-{$tab}\"]/form/div/section";
            $actual = $this->xpathCount($xpath, $query);

            if ($actual !== $expected) {
                $mismatches[] = "{$tab}: DOM 顶层 section={$actual}, 源码={$expected}";
            }
        }

        $this->assertSame([], $mismatches, '设置面板 section 结构错位: '.implode('; ', $mismatches));
    }

    #[Test]
    public function seo_panel_dom_has_balanced_sections_and_clean_submit_button(): void
    {
        $html = (string) $this->actingAs($this->admin)
            ->get('/admin/settings?tab=seo')
            ->assertOk()
            ->getContent();

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // 1) 无任何 <a>/<label> 吞掉表单或保存按钮（浏览器错误恢复类 bug 免疫）
        $this->assertSame(0,
            $this->xpathCount($xpath, '//a[.//form] | //a[.//button[@type="submit"]] | //label[.//form] | //label[.//button[@type="submit"]]'),
            '存在被 <a>/<label> 吞并的表单/保存按钮');

        // 2) seo 面板：3 个顶层 section（修复前 section2/3 错误嵌在 section1 内）
        $this->assertSame(3, $this->xpathCount($xpath, '//div[@id="panel-seo"]/form/div/section'));

        // 3) sitemap/domain monitor 两个 checkbox 归位于第一个 section 内
        $this->assertSame(
            1,
            $this->xpathCount($xpath, '//div[@id="panel-seo"]/form/div/section[1]//input[@name="sitemap_monitor_is_enabled"]'),
            'sitemap_monitor_is_enabled 未在第一个 section 内'
        );
        $this->assertSame(
            1,
            $this->xpathCount($xpath, '//div[@id="panel-seo"]/form/div/section[1]//input[@name="domain_monitor_is_enabled"]'),
            'domain_monitor_is_enabled 未在第一个 section 内'
        );

        // 4) serpapi 字段旁的帮助文章链接在新标签打开
        $docLinkQuery = '//div[@id="panel-seo"]//a[@href="'.route('help.article', 'serpapi-key').'"]';
        $this->assertSame(1, $this->xpathCount($xpath, $docLinkQuery));
        $docLink = $this->firstElement($xpath, $docLinkQuery);
        $this->assertInstanceOf(\DOMElement::class, $docLink);
        $this->assertSame('_blank', $docLink->attributes->getNamedItem('target')?->nodeValue);
    }

    /**
     * DOMXPath::query 的 false 兼容计数（PHPStan L10：DOMNodeList|false 联合）
     */
    protected function xpathCount(\DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);

        return $nodes === false ? 0 : $nodes->length;
    }

    /**
     * 取首个 DOM 节点（空结果返回 null）
     */
    protected function firstElement(\DOMXPath $xpath, string $query): ?\DOMNode
    {
        $nodes = $xpath->query($query);
        $node = ($nodes === false || $nodes->length === 0) ? null : $nodes->item(0);

        return $node instanceof \DOMNode ? $node : null;
    }
}
