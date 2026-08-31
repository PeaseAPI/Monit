<?php

namespace Tests\Feature;

use App\Mail\PlanLimitNotice;
use App\Mail\UserDeletionReminder;
use App\Models\OutboundClick;
use App\Models\SessionEvent;
use App\Models\User;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * M22 原版对齐验证（规格书 §5.1.1 / §5.5 / §13.1）
 * - 统计页：时区/大洲/主题/星期分布
 * - 引荐分类（社交/搜索引擎/AI）与 3 个钻取
 * - Cron 补齐：配额通知 / 不活跃用户提醒与删除 / 数据清理
 * - 访客导出 JSON/CSV
 */
class M22Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'M22', 'email' => 'm22@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'sessions_replays_limit' => -1, 'export' => 1],
        ]);

        $this->website = Website::create([
            'user_id' => $this->user->user_id,
            'pixel_key' => 'px_m22', 'name' => 'M22 Site',
            'scheme' => 'https', 'host' => 'm22.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);
    }

    protected function makeVisitor(array $attrs = []): WebsiteVisitor
    {
        return WebsiteVisitor::create(array_merge([
            'website_id' => $this->website->website_id,
            'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
            'country_code' => 'CN', 'device_type' => 'desktop',
            'os_name' => 'macOS', 'browser_name' => 'Chrome',
            'date' => now(), 'last_date' => now(),
        ], $attrs));
    }

    protected function makeSession(WebsiteVisitor $visitor, array $attrs = []): VisitorSession
    {
        return VisitorSession::create(array_merge([
            'website_id' => $this->website->website_id,
            'visitor_id' => $visitor->visitor_id,
            'session_uuid_binary' => Uuid::uuid4()->getBytes(),
            'date' => now(), 'total_events' => 0,
        ], $attrs));
    }

    protected function makeEvent(WebsiteVisitor $v, VisitorSession $s, string $type, string $path, array $attrs = []): SessionEvent
    {
        return SessionEvent::create(array_merge([
            'event_uuid_binary' => Uuid::uuid4()->getBytes(),
            'session_id' => $s->session_id, 'visitor_id' => $v->visitor_id,
            'website_id' => $this->website->website_id,
            'type' => $type, 'path' => $path,
            'has_bounced' => false, 'date' => now(),
            'expiration_date' => now()->addDays(365),
        ], $attrs));
    }

    /* ---------------- 统计页 ---------------- */

    public function test_weekday_series_has_7_buckets(): void
    {
        $v = $this->makeVisitor();
        $s = $this->makeSession($v);
        $this->makeEvent($v, $s, 'landing_page', '/');

        $weekdays = StatisticsService::for($this->website)->lastDays(7)->weekdaySeries();

        $this->assertCount(7, $weekdays);
        $this->assertSame(1, array_sum(array_column($weekdays, 'pageviews')));
        $this->assertSame(__('stats.weekday_mon'), $weekdays[0]['label']);
        $this->assertSame(__('stats.weekday_sun'), $weekdays[6]['label']);
    }

    public function test_breakdown_supports_timezone_continent_theme(): void
    {
        $v1 = $this->makeVisitor(['browser_timezone' => 'Asia/Shanghai', 'continent_code' => 'AS', 'theme' => 'dark']);
        $s1 = $this->makeSession($v1);
        $this->makeEvent($v1, $s1, 'landing_page', '/');

        $svc = StatisticsService::for($this->website)->lastDays(7);

        $this->assertSame('Asia/Shanghai', $svc->breakdown('browser_timezone')[0]['key']);
        $this->assertSame('AS', $svc->breakdown('continent_code')[0]['key']);
        $this->assertSame('dark', $svc->breakdown('theme')[0]['key']);
    }

    public function test_referral_categories_classifies_social_search_ai(): void
    {
        $v = $this->makeVisitor();
        $s = $this->makeSession($v);

        $this->makeEvent($v, $s, 'landing_page', '/', ['referrer_host' => 'chat.openai.com']);
        $this->makeEvent($v, $s, 'landing_page', '/', ['referrer_host' => 'claude.ai']);
        $this->makeEvent($v, $s, 'landing_page', '/', ['referrer_host' => 't.co']);
        $this->makeEvent($v, $s, 'landing_page', '/', ['referrer_host' => 'l.facebook.com']);
        $this->makeEvent($v, $s, 'landing_page', '/', ['referrer_host' => 'www.google.com.hk']);
        $this->makeEvent($v, $s, 'landing_page', '/', ['referrer_host' => 'www.baidu.com']);

        $cats = StatisticsService::for($this->website)->lastDays(7)->referralCategories();

        $aiKeys = array_column($cats['ai'], 'key');
        $this->assertEqualsCanonicalizing(['openai.com', 'claude.ai'], $aiKeys);

        $socialKeys = array_column($cats['social'], 'key');
        $this->assertContains('x.com', $socialKeys);
        $this->assertContains('facebook.com', $socialKeys);

        $searchKeys = array_column($cats['search'], 'key');
        $this->assertEqualsCanonicalizing(['google.com', 'baidu.com'], $searchKeys);
    }

    public function test_referrer_paths_and_utm_drilldown(): void
    {
        $v = $this->makeVisitor();
        $s = $this->makeSession($v);

        $this->makeEvent($v, $s, 'landing_page', '/a', [
            'referrer_host' => 'news.example.com',
            'referrer_path' => '/story/1',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'launch',
        ]);
        $this->makeEvent($v, $s, 'landing_page', '/b', [
            'referrer_host' => 'news.example.com',
            'referrer_path' => '/story/2',
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'launch',
        ]);

        $svc = StatisticsService::for($this->website)->lastDays(7);

        $paths = $svc->referrerPaths('news.example.com');
        $this->assertCount(2, $paths);
        $this->assertEqualsCanonicalizing(['/story/1', '/story/2'], array_column($paths, 'key'));

        $drill = $svc->utmDrilldown('newsletter');
        $this->assertCount(1, $drill);
        $this->assertSame('email', $drill[0]['medium']);
        $this->assertSame('launch', $drill[0]['campaign']);
        $this->assertSame(2, $drill[0]['count']);
    }

    public function test_outbound_click_paths_drilldown(): void
    {
        OutboundClick::create(['website_id' => $this->website->website_id, 'host' => 'partner.com', 'path' => '/offer', 'datetime' => now()]);
        OutboundClick::create(['website_id' => $this->website->website_id, 'host' => 'partner.com', 'path' => '/offer', 'datetime' => now()]);
        OutboundClick::create(['website_id' => $this->website->website_id, 'host' => 'other.com', 'path' => '/x', 'datetime' => now()]);

        $paths = StatisticsService::for($this->website)->lastDays(7)->outboundClickPaths('partner.com');

        $this->assertCount(1, $paths);
        $this->assertSame('/offer', $paths[0]['key']);
        $this->assertSame(2, $paths[0]['count']);
    }

    public function test_new_stats_pages_return_200(): void
    {
        $this->actingAs($this->user, 'web');

        $pages = [
            'stats.top_timezones', 'stats.top_continents', 'stats.top_themes',
            'stats.referral_categories', 'stats.behavior',
        ];

        foreach ($pages as $route) {
            $this->get(route($route, $this->website->website_id))->assertOk();
        }
    }

    /* ---------------- Cron 补齐 ---------------- */

    public function test_websites_limit_notice_marks_flag(): void
    {
        Mail::fake();

        DB::table('settings')->updateOrInsert(['key' => 'email_notices_is_enabled'], ['value' => true]);

        $this->user->forceFill(['plan_settings' => ['sessions_events_limit' => 5]])->save();
        $this->website->forceFill(['current_month_sessions_events' => 10, 'plan_sessions_events_limit_notice' => false])->save();

        $this->artisan('monit:websites-limit-notice')->assertSuccessful();

        $this->assertTrue($this->website->fresh()->plan_sessions_events_limit_notice);
        Mail::assertQueued(PlanLimitNotice::class);
    }

    public function test_users_deletion_reminder_and_auto_delete(): void
    {
        Mail::fake();

        DB::table('settings')->updateOrInsert(['key' => 'auto_delete_inactive_users'], ['value' => 30]);
        DB::table('settings')->updateOrInsert(['key' => 'user_deletion_reminder'], ['value' => 7]);

        $inactive = User::create([
            'name' => 'Inactive', 'email' => 'inactive@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
            'last_activity' => now()->subDays(25),
        ]);

        // 25 天不活跃 + 提前 7 天提醒（30-7=23 天阈值）→ 触发提醒
        $this->artisan('monit:users-deletion-reminder')->assertSuccessful();

        $this->assertTrue($inactive->fresh()->user_deletion_reminder);
        Mail::assertQueued(UserDeletionReminder::class);

        // 超过 30 天 + 已提醒 → 删除
        $inactive->fresh()->forceFill(['last_activity' => now()->subDays(31)])->save();

        $this->artisan('monit:auto-delete-inactive-users')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['user_id' => $inactive->user_id]);
    }

    public function test_housekeeping_cleanup_deletes_old_logs(): void
    {
        DB::table('account_logs')->insert([
            'user_id' => $this->user->user_id, 'type' => 'login',
            'datetime' => now()->subDays(100),
        ]);
        DB::table('account_logs')->insert([
            'user_id' => $this->user->user_id, 'type' => 'login',
            'datetime' => now()->subDays(10),
        ]);

        $this->artisan('monit:housekeeping-cleanup')->assertSuccessful();

        $this->assertSame(1, DB::table('account_logs')->count());
        $this->assertSame('login', DB::table('account_logs')->first()->type);
    }

    /* ---------------- 访客导出 ---------------- */

    public function test_visitors_export_json_and_csv(): void
    {
        $this->actingAs($this->user, 'web');

        $this->makeVisitor(['city_name' => 'Shanghai', 'browser_timezone' => 'Asia/Shanghai']);

        $json = $this->get(route('stats.visitors', ['website' => $this->website->website_id, 'export' => 'json']))->assertOk();
        $json->assertJson(['count' => 1]);

        $csv = $this->get(route('stats.visitors', ['website' => $this->website->website_id, 'export' => 'csv']))->assertOk();
        $this->assertStringContainsString('Shanghai', $csv->streamedContent());
    }
}
