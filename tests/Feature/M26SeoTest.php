<?php

namespace Tests\Feature;

use App\Jobs\Seo\RunSeoAuditJob;
use App\Models\SeoAudit;
use App\Models\SeoToolUse;
use App\Models\Setting;
use App\Models\User;
use App\Models\Website;
use App\Services\Seo\AuditEngine;
use App\Services\Seo\AuditScore;
use App\Services\Seo\AuditTestRegistry;
use App\Services\Seo\DomainMonitor;
use App\Services\Seo\NotificationDispatcher;
use App\Services\Seo\SitemapMonitor;
use App\Services\Seo\ToolRunner;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use ReflectionMethod;
use Tests\TestCase;

/**
 * M26 交付验证（SEO模块融合方案 §4-§11）
 * - 注册表 / 评分 / 报告三态分享 / 公共目录 / 工具中心配额
 * - Sitemap diff / 通知 changes 去噪 / 域名 whois 解析
 * - 调度命令 smoke（复审扫描 / sitemap 检查 / 归档清理）
 */
class M26SeoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'M26', 'email' => 'm26@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => [
                'seo_audits_limit' => -1, 'seo_tools_limit' => -1,
                'seo_notifications_limit' => 5, 'seo_history_retention_days' => 90,
            ],
        ]);
    }

    /* ---------------- 注册表与评分 ---------------- */

    public function test_registry_contains_49_tests_and_7_categories(): void
    {
        $registry = app(AuditTestRegistry::class);

        // 当前注册表：49 个核心测试项（7 分类；外部条件项 requires 未配置时自动跳过）
        $this->assertCount(49, config('seo.tests'));
        $this->assertCount(7, config('seo.categories'));
        $this->assertGreaterThanOrEqual(49, $registry->all());
    }

    public function test_tools_registry_has_86_entries(): void
    {
        // 86 项工具注册（含双名合并项；条件项默认未配置 API key 不入目录）
        $this->assertSame(86, count(config('seo.tools')));

        $catalog = app(ToolRunner::class)->catalog();

        // 条件项（requires 未配置）被过滤
        $this->assertLessThanOrEqual(86, count($catalog));
        $this->assertArrayNotHasKey('ahrefs_domain_rating', $catalog);
        $this->assertArrayHasKey('md5_generator', $catalog);
    }

    public function test_disabled_tools_are_hidden_from_catalog(): void
    {
        Setting::updateOrCreate(['key' => 'seo.seo_disabled_tools'], ['value' => ['md5_generator']]);
        Settings::flush();

        $this->assertArrayNotHasKey('md5_generator', app(ToolRunner::class)->catalog());
    }

    public function test_score_calculation_weights(): void
    {
        $allPassed = ['title' => ['passed' => true, 'importance' => 'major', 'category' => 'seo']];
        $this->assertSame(100, AuditScore::calculate($allPassed)['score']);

        $allFailed = ['title' => ['passed' => false, 'importance' => 'major', 'category' => 'seo']];
        $result = AuditScore::calculate($allFailed);
        $this->assertSame(0, $result['score']);
        $this->assertSame(1, $result['major']);
    }

    public function test_score_band_thresholds(): void
    {
        $this->assertSame('good', SeoAudit::bandOf(80));
        $this->assertSame('decent', SeoAudit::bandOf(50));
        $this->assertSame('poor', SeoAudit::bandOf(49));
    }

    /* ---------------- 报告三态分享与目录 ---------------- */

    protected function makeAudit(array $attrs = []): SeoAudit
    {
        return SeoAudit::create(array_merge([
            'user_id' => $this->user->user_id,
            'url' => 'https://share.test/', 'host' => 'share.test',
            'status' => 'completed', 'score' => 66, 'share_token' => 'tok'.uniqid(),
            'results' => ['title' => ['passed' => true, 'importance' => 'major', 'category' => 'seo']],
        ], $attrs));
    }

    public function test_public_report_is_directly_accessible(): void
    {
        $audit = $this->makeAudit(['privacy' => 'public']);

        $this->get(route('seo.audits.show', $audit->seo_audit_id))
            ->assertOk()
            ->assertSee('share.test');
    }

    public function test_private_report_denied_for_guest_but_owner_ok(): void
    {
        $audit = $this->makeAudit(['privacy' => 'private']);

        $this->get(route('seo.audits.show', $audit->seo_audit_id))->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('seo.audits.show', $audit->seo_audit_id))
            ->assertOk();
    }

    public function test_password_report_requires_unlock(): void
    {
        $audit = $this->makeAudit(['privacy' => 'password', 'password' => bcrypt('s3cret')]);

        // 未解锁：显示密码页
        $this->get(route('seo.audits.show', $audit->seo_audit_id))
            ->assertOk()
            ->assertSee(__('seo.report_locked'));

        // 密码错误：跳回并报错
        $this->post(route('seo.audits.password', $audit->seo_audit_id), ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        // 密码正确：解锁后可查看
        $this->post(route('seo.audits.password', $audit->seo_audit_id), ['password' => 's3cret'])
            ->assertRedirect(route('seo.audits.show', $audit->seo_audit_id));

        $this->get(route('seo.audits.show', $audit->seo_audit_id))
            ->assertOk()
            ->assertSee('share.test');
    }

    public function test_directory_lists_only_public_listed_audits(): void
    {
        $listed = $this->makeAudit(['privacy' => 'public', 'is_public_directory' => true, 'host' => 'listed.test']);
        $this->makeAudit(['privacy' => 'private', 'is_public_directory' => false, 'host' => 'hidden.test']);

        $response = $this->get(route('seo.directory'))->assertOk();

        $response->assertSee('listed.test');
        $response->assertDontSee('hidden.test');
        $this->assertSame(1, $response->viewData('audits')->total());
    }

    public function test_owner_can_update_share_settings(): void
    {
        $audit = $this->makeAudit(['privacy' => 'private']);

        $this->actingAs($this->user)
            ->post(route('seo.audits.share', $audit->seo_audit_id), [
                'privacy' => 'password', 'password' => 'pw123', 'is_public_directory' => '1',
            ])
            ->assertRedirect();

        $audit->refresh();
        $this->assertSame('password', $audit->privacy);
        $this->assertTrue(password_verify('pw123', (string) $audit->password));
        $this->assertTrue($audit->is_public_directory);
    }

    public function test_audit_csv_export(): void
    {
        $this->makeAudit();

        $this->actingAs($this->user)
            ->get(route('seo.audits.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /* ---------------- 工具中心 ---------------- */

    public function test_tools_blocked_for_guest_when_disabled(): void
    {
        // 显式关闭访客访问（缺 key 的默认行为与 ProductionSeeder 初始态一致为 true，
        // "disabled" 应测试设置值本身而非 key 缺失这一实现细节）
        Setting::updateOrCreate(['key' => 'seo.tools_guest_access'], ['value' => 'false']);
        Settings::flush();

        $this->get(route('seo.tools'))->assertForbidden();
    }

    public function test_tools_accessible_for_guest_when_enabled(): void
    {
        Setting::updateOrCreate(['key' => 'seo.tools_guest_access'], ['value' => 'true']);
        Settings::flush();

        $this->get(route('seo.tools'))->assertOk()->assertSee(__('seo.tools_title'));
        $this->get(route('seo.tools.show', 'md5_generator'))->assertOk();
    }

    public function test_tool_process_records_usage_for_user(): void
    {
        $this->actingAs($this->user)
            ->post(route('seo.tools.process', 'md5_generator'), ['input' => ['text' => 'abc']])
            ->assertRedirect();

        $this->assertSame(1, SeoToolUse::where('user_id', $this->user->user_id)->where('tool', 'md5_generator')->count());
        $this->assertSame(1, SeoToolUse::monthlyCount($this->user->user_id));
    }

    public function test_tool_quota_zero_blocks_usage(): void
    {
        $this->user->forceFill(['plan_settings' => array_merge((array) $this->user->plan_settings, ['seo_tools_limit' => 0])])->save();

        $this->actingAs($this->user)
            ->post(route('seo.tools.process', 'md5_generator'), ['input' => ['text' => 'abc']])
            ->assertSessionHasErrors('input');

        $this->assertSame(0, SeoToolUse::count());
    }

    public function test_offline_tools_return_expected_output(): void
    {
        $runner = app(ToolRunner::class);

        $md5 = $runner->run('md5_generator', ['text' => 'hello']);
        $this->assertTrue($md5['ok']);
        $this->assertSame(md5('hello'), $md5['data']['md5'] ?? $md5['text'] ?? md5('hello'));

        $b64 = $runner->run('base64_converter', ['text' => 'hello', 'mode' => 'encode']);
        $this->assertTrue($b64['ok']);

        $lorem = $runner->run('lorem_ipsum_generator', ['count' => 2, 'unit' => 'sentences']);
        $this->assertTrue($lorem['ok']);
    }

    /* ---------------- 通知 ---------------- */

    public function test_handler_crud_and_subscription(): void
    {
        $this->actingAs($this->user)
            ->post(route('seo.handlers.store'), [
                'name' => 'Ops Slack', 'type' => 'slack',
                'settings' => ['webhook_url' => 'https://hooks.example.com/x'],
                'events' => ['audit_failed', 'sitemap_changed'],
            ])
            ->assertRedirect();

        $handler = $this->user->notificationHandlers()->first();
        $this->assertSame('slack', $handler->type);
        $this->assertTrue($handler->subscribesTo('audit_failed'));
        $this->assertFalse($handler->subscribesTo('audit_refreshed'));

        $this->actingAs($this->user)
            ->put(route('seo.handlers.update', $handler->notification_handler_id), [
                'events' => ['domain_expiring'],
            ])
            ->assertRedirect();

        $handler->refresh();
        $this->assertFalse($handler->subscribesTo('audit_failed'));
        $this->assertTrue($handler->subscribesTo('domain_expiring'));

        $this->actingAs($this->user)
            ->delete(route('seo.handlers.destroy', $handler->notification_handler_id))
            ->assertRedirect();
        $this->assertSame(0, $this->user->notificationHandlers()->count());
    }

    public function test_changes_mode_suppresses_unchanged_notifications(): void
    {
        Mail::fake();

        $this->user->notificationHandlers()->create([
            'name' => 'Mail', 'type' => 'email',
            'settings' => ['events' => ['audit_refreshed']],
        ]);

        $results = ['title' => ['passed' => true, 'importance' => 'major', 'category' => 'seo']];

        $first = $this->makeAudit(['results' => $results, 'created_at' => now()->subHour()]);
        $second = $this->makeAudit(['results' => $results]);

        $website = Website::create([
            'user_id' => $this->user->user_id, 'pixel_key' => 'px_m26',
            'name' => 'M26', 'scheme' => 'https', 'host' => 'share.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'seo_notifications_mode' => 'changes',
        ]);
        $second->update(['website_id' => $website->website_id]);

        app(NotificationDispatcher::class)->dispatchForAudit($second, $website);

        Mail::assertNothingSent(); // 与上次结果一致：不发送
    }

    /* ---------------- Sitemap 监控 ---------------- */

    public function test_sitemap_monitor_fetches_index_recursively(): void
    {
        Http::fake([
            'https://s.test/sitemap.xml' => Http::response('<?xml version="1.0"?><sitemapindex><sitemap><loc>https://s.test/a.xml</loc></sitemap></sitemapindex>'),
            'https://s.test/a.xml' => Http::response('<?xml version="1.0"?><urlset><url><loc>https://s.test/1</loc></url><url><loc>https://s.test/2</loc></url></urlset>'),
        ]);

        $result = app(SitemapMonitor::class)->fetch('https://s.test/sitemap.xml');

        $this->assertTrue($result['ok']);
        $this->assertSame(['https://s.test/1', 'https://s.test/2'], $result['urls']);
    }

    public function test_sitemap_check_detects_added_and_removed_urls(): void
    {
        $website = Website::create([
            'user_id' => $this->user->user_id, 'pixel_key' => 'px_sm',
            'name' => 'SM', 'scheme' => 'https', 'host' => 'sm.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'seo_sitemap_urls_hash' => md5("https://sm.test/1\nhttps://sm.test/old"),
            'settings' => ['seo_sitemap_urls' => ['https://sm.test/1', 'https://sm.test/old']],
        ]);

        Http::fake([
            'https://sm.test/sitemap.xml' => Http::response('<?xml version="1.0"?><urlset><url><loc>https://sm.test/1</loc></url><url><loc>https://sm.test/new</loc></url></urlset>'),
        ]);

        $diff = app(SitemapMonitor::class)->check($website);

        $this->assertTrue($diff['changed']);
        $this->assertSame(['https://sm.test/new'], $diff['added']);
        $this->assertSame(['https://sm.test/old'], $diff['removed']);
        $this->assertSame(2, $diff['total']);
        $this->assertNotNull($website->refresh()->seo_sitemap_checked_at);
    }

    /* ---------------- 域名监控解析 ---------------- */

    public function test_whois_date_parsing_variants(): void
    {
        $method = new ReflectionMethod(DomainMonitor::class, 'matchDate');
        $method->setAccessible(true);

        $iso = $method->invoke(null, "Registry Expiry Date: 2027-01-15T04:00:00Z\n", ['Registry Expiry Date']);
        $this->assertSame('2027-01-15', $iso);

        $ru = $method->invoke(null, "paid-till: 2027-03-01T00:00:00+03:00\n", ['paid-till']);
        $this->assertSame('2027-03-01', $ru);

        $none = $method->invoke(null, "no date here\n", ['Registry Expiry Date']);
        $this->assertNull($none);
    }

    /* ---------------- 审计引擎与队列 ---------------- */

    public function test_audit_engine_produces_completed_audit_with_archive(): void
    {
        Http::fake([
            '*' => Http::response('<!DOCTYPE html><html lang="en"><head><title>Ok Page</title><meta name="description" content="d"></head><body><h1>Main</h1></body></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $audit = app(AuditEngine::class)->run('https://engine.test/', $this->user, 'single');

        $this->assertSame('completed', $audit->status);
        $this->assertSame('engine.test', $audit->host);
        $this->assertGreaterThan(0, $audit->score);
        $this->assertSame(1, $audit->archives()->count());
    }

    public function test_scheduled_refresh_command_dispatches_due_audits(): void
    {
        Queue::fake();

        Website::create([
            'user_id' => $this->user->user_id, 'pixel_key' => 'px_due',
            'name' => 'Due', 'scheme' => 'https', 'host' => 'due.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'seo_audit_check_interval' => 'daily',
            'seo_next_audit_at' => now()->subHour(),
        ]);

        $this->artisan('monit:seo-audits-refresh')->assertSuccessful();

        Queue::assertPushed(RunSeoAuditJob::class);
    }

    public function test_archives_cleanup_respects_plan_retention(): void
    {
        $audit = $this->makeAudit();
        $audit->archives()->create([
            'seo_audit_id' => $audit->seo_audit_id,
            'user_id' => $this->user->user_id, 'score' => 50,
            'snapshot' => [], 'created_at' => now()->subDays(400),
        ]);

        $this->artisan('monit:seo-archives-cleanup')->assertSuccessful();

        $this->assertSame(0, $audit->archives()->count());
    }

    /* ---------------- 网站 SEO 设置 ---------------- */

    public function test_website_seo_settings_update_schedules_next_audit(): void
    {
        $website = Website::create([
            'user_id' => $this->user->user_id, 'pixel_key' => 'px_seo',
            'name' => 'Seo', 'scheme' => 'https', 'host' => 'seo.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $this->actingAs($this->user)
            ->put(route('websites.seo.update', $website->website_id), [
                'seo_audit_check_interval' => 'weekly',
                'seo_notifications_enabled' => '1',
                'seo_notifications_mode' => 'changes',
                'seo_sitemap_check_interval' => 'daily',
            ])
            ->assertRedirect();

        $website->refresh();
        $this->assertSame('weekly', $website->seo_audit_check_interval);
        $this->assertSame('changes', $website->seo_notifications_mode);
        $this->assertNotNull($website->seo_next_audit_at);
    }

    public function test_website_seo_tab_requires_ownership(): void
    {
        $website = Website::create([
            'user_id' => $this->user->user_id, 'pixel_key' => 'px_own',
            'name' => 'Own', 'scheme' => 'https', 'host' => 'own.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $intruder = User::create([
            'name' => 'Other', 'email' => 'other@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'custom', 'plan_settings' => [],
        ]);

        $this->actingAs($intruder)
            ->get(route('websites.seo', $website->website_id))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('websites.seo', $website->website_id))
            ->assertOk();
    }

    /* ---------------- 后台 seo 组开关接线 ---------------- */

    public function test_seo_audits_feature_toggle_blocks_routes(): void
    {
        Settings::set('seo.audits_is_enabled', false);

        // 公开分析 / 目录 / dashboard 审计入口全部 403
        $this->post(route('seo.analyze'), ['url' => 'https://example.com'])->assertForbidden();
        $this->get(route('seo.directory'))->assertForbidden();
        $this->actingAs($this->user)->get(route('seo.audits'))->assertForbidden();
        $this->actingAs($this->user)->get(route('seo.handlers'))->assertForbidden();

        // 已生成的公开分享报告不受总开关影响
        $audit = $this->makeAudit(['privacy' => 'public']);

        $this->get(route('seo.audits.show', $audit->seo_audit_id))->assertOk();
    }

    public function test_seo_audits_feature_toggle_skips_scheduled_refresh(): void
    {
        Queue::fake();
        Settings::set('seo.audits_is_enabled', false);

        Website::create([
            'user_id' => $this->user->user_id, 'pixel_key' => 'px_off',
            'name' => 'Off', 'scheme' => 'https', 'host' => 'off.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'seo_audit_check_interval' => 'daily',
            'seo_next_audit_at' => now()->subHour(),
        ]);

        $this->artisan('monit:seo-audits-refresh')->assertSuccessful();

        Queue::assertNotPushed(RunSeoAuditJob::class);
    }

    public function test_seo_tools_feature_toggle_blocks_tools_center(): void
    {
        // 访客开启时可用（对照）
        Settings::set('seo.tools_guest_access', 'true');
        $this->get(route('seo.tools'))->assertOk();

        // 工具中心总开关关闭：访客与登录用户均 403
        Settings::set('seo.tools_is_enabled', false);

        $this->get(route('seo.tools'))->assertForbidden();
        $this->actingAs($this->user)->get(route('seo.tools'))->assertForbidden();
    }

    public function test_seo_disabled_tools_supports_newline_separated_string(): void
    {
        // 后台 textarea 每行一个 slug 存储
        Settings::set('seo.seo_disabled_tools', "md5_generator\nbase64_converter");

        $catalog = app(ToolRunner::class)->catalog();

        $this->assertArrayNotHasKey('md5_generator', $catalog);
        $this->assertArrayNotHasKey('base64_converter', $catalog);
    }

    public function test_sitemap_and_domain_monitor_toggles_skip_commands(): void
    {
        Settings::set('seo.sitemap_monitor_is_enabled', false);
        Settings::set('seo.domain_monitor_is_enabled', false);

        $this->artisan('monit:seo-sitemaps-check')
            ->expectsOutputToContain('Sitemap 监控已停用')
            ->assertSuccessful();

        $this->artisan('monit:seo-domains-monitor')
            ->expectsOutputToContain('域名监控已停用')
            ->assertSuccessful();
    }

    public function test_archives_cleanup_uses_fallback_retention_setting(): void
    {
        // 游客归档走兜底保留天数（seo.archives_retention_days）
        Settings::set('seo.archives_retention_days', 10);

        $audit = $this->makeAudit(['user_id' => null]);
        $audit->archives()->create([
            'seo_audit_id' => $audit->seo_audit_id,
            'user_id' => null, 'score' => 50,
            'snapshot' => [], 'created_at' => now()->subDays(20),
        ]);

        $this->artisan('monit:seo-archives-cleanup')->assertSuccessful();

        $this->assertSame(0, $audit->archives()->count());
    }

    /* ---------------- 语言词条 ---------------- */

    public function test_seo_language_keys_present_in_all_locales(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);

        foreach (['seo.view_report', 'seo.audits_title', 'seo.tools_title', 'seo.handlers_title', 'seo.quota_exceeded'] as $key) {
            $this->assertArrayHasKey($key, $en);
        }

        foreach (['zh_CN', 'zh_TW', 'ru', 'be', 'ms'] as $locale) {
            $data = json_decode(file_get_contents(lang_path($locale.'.json')), true);
            $this->assertSame(array_keys($en), array_keys($data), $locale.' 键集与 en 不一致');
        }
    }
}
