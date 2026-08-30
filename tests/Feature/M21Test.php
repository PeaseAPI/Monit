<?php

namespace Tests\Feature;

use App\Models\SessionEvent;
use App\Models\User;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * M21 GA/CNZZ 功能对标验证（规格书 §6 扩展）
 * - 时段分析（CNZZ）/ 渠道分组（GA 默认渠道）/ 入口页 / 离开页
 * - 搜索词（搜索引擎 referrer 解析）/ 忠诚度（新老访客 + 频次/深度/时长）
 * - 城市与语言与分辨率维度 / 行为分析页面
 */
class M21Test extends TestCase
{
    use RefreshDatabase;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'M21', 'email' => 'm21@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1],
        ]);

        $this->website = Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_m21', 'name' => 'M21 Site',
            'scheme' => 'https', 'host' => 'm21.test',
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

    public function test_hourly_series_has_24_buckets(): void
    {
        $v = $this->makeVisitor();
        $s = $this->makeSession($v);

        $this->makeEvent($v, $s, 'landing_page', '/', ['date' => now()->setTime(9, 10)]);
        $this->makeEvent($v, $s, 'pageview', '/a', ['date' => now()->setTime(15, 30)]);

        $hourly = StatisticsService::for($this->website)->lastDays(7)->hourlySeries();

        $this->assertCount(24, $hourly);
        $this->assertSame(1, $hourly[9]['pageviews']);
        $this->assertSame(1, $hourly[15]['pageviews']);
        $this->assertSame(0, $hourly[0]['pageviews']);
        $this->assertSame('09:00', $hourly[9]['label']);
    }

    public function test_landing_and_exit_pages(): void
    {
        $v1 = $this->makeVisitor();
        $v2 = $this->makeVisitor();
        $s1 = $this->makeSession($v1);
        $s2 = $this->makeSession($v2);

        $this->makeEvent($v1, $s1, 'landing_page', '/');
        $this->makeEvent($v1, $s1, 'pageview', '/a');
        $this->makeEvent($v1, $s1, 'pageview', '/b'); // s1 最后事件
        $this->makeEvent($v2, $s2, 'landing_page', '/x');

        $svc = StatisticsService::for($this->website)->lastDays(7);

        $landings = $svc->landingPages();
        $this->assertEqualsCanonicalizing(['/', '/x'], array_column($landings, 'key'));

        $exits = $svc->exitPages();
        $this->assertEqualsCanonicalizing(['/b', '/x'], array_column($exits, 'key'));
    }

    public function test_search_terms_extracts_from_search_engines(): void
    {
        $v = $this->makeVisitor();
        $s = $this->makeSession($v);

        $this->makeEvent($v, $s, 'landing_page', '/', [
            'referrer_host' => 'www.baidu.com', 'referrer_path' => '/s?wd=laravel%20%E7%BB%9F%E8%AE%A1&rn=10',
        ]);
        $this->makeEvent($v, $s, 'landing_page', '/p1b', [
            'referrer_host' => 'm.baidu.com', 'referrer_path' => '/s?word=laravel%20%E7%BB%9F%E8%AE%A1',
        ]);
        $this->makeEvent($v, $s, 'landing_page', '/p2', [
            'referrer_host' => 'google.com', 'referrer_path' => '/search?q=analytics&ie=utf-8',
        ]);
        $this->makeEvent($v, $s, 'landing_page', '/p3', [
            'referrer_host' => 'news.example.com', 'referrer_path' => '/story?id=1',
        ]);

        $terms = StatisticsService::for($this->website)->lastDays(7)->searchTerms();

        $this->assertCount(2, $terms); // 普通外链不计
        $this->assertSame('laravel 统计', $terms[0]['key']);
        $this->assertStringContainsString('baidu.com', $terms[0]['engines']);
        $this->assertSame('analytics', $terms[1]['key']);
    }

    public function test_channels_grouping(): void
    {
        $v = $this->makeVisitor();
        $s = $this->makeSession($v);

        $this->makeEvent($v, $s, 'landing_page', '/d1'); // 无 referrer → direct
        $this->makeEvent($v, $s, 'landing_page', '/d2', ['referrer_host' => 'm21.test']); // 自站 → direct
        $this->makeEvent($v, $s, 'landing_page', '/o', ['referrer_host' => 'www.bing.com', 'referrer_path' => '/search?q=x']);
        $this->makeEvent($v, $s, 'landing_page', '/so', ['referrer_host' => 'weibo.com']);
        $this->makeEvent($v, $s, 'landing_page', '/r', ['referrer_host' => 'partner.example.com']);
        $this->makeEvent($v, $s, 'landing_page', '/c', ['utm_source' => 'newsletter', 'utm_medium' => 'email']);

        $channels = StatisticsService::for($this->website)->lastDays(7)->channels();

        $this->assertSame(2, $channels['direct']);
        $this->assertSame(1, $channels['organic']);
        $this->assertSame(1, $channels['social']);
        $this->assertSame(1, $channels['referral']);
        $this->assertSame(1, $channels['campaign']);
    }

    public function test_loyalty_buckets(): void
    {
        $va = $this->makeVisitor();
        $vb = $this->makeVisitor();

        // 访客 A：2 个会话（回访）；访客 B：1 个会话（新访客）
        $s1 = $this->makeSession($va, ['total_events' => 3, 'date' => now()->subMinutes(5)]);
        $s2 = $this->makeSession($va);
        $s3 = $this->makeSession($vb, ['total_events' => 1]);

        $this->makeEvent($va, $s1, 'landing_page', '/'); // 事件时间为 now → 时长 5 分钟

        $loyalty = StatisticsService::for($this->website)->lastDays(7)->loyalty();

        $this->assertSame(1, $loyalty['new_visitors']);
        $this->assertSame(1, $loyalty['returning_visitors']);

        $freq = collect($loyalty['frequency'])->pluck('count', 'key');
        $this->assertSame(1, $freq['1']);
        $this->assertSame(1, $freq['2']);

        $depth = collect($loyalty['depth'])->pluck('count', 'key');
        $this->assertSame(2, $depth['1']);     // s2(0)与 s3(1)均 ≤1
        $this->assertSame(1, $depth['2-3']);   // s1 total_events=3

        $duration = collect($loyalty['duration'])->pluck('count', 'key');
        $this->assertSame(1, $duration['3m+']); // 5 分钟会话
    }

    public function test_screen_resolution_and_language_dimensions(): void
    {
        $v1 = $this->makeVisitor(['screen_resolution' => '1920x1080', 'browser_language' => 'zh-CN']);
        $s1 = $this->makeSession($v1);
        $this->makeEvent($v1, $s1, 'landing_page', '/');

        $svc = StatisticsService::for($this->website)->lastDays(7);

        $this->assertSame('1920x1080', $svc->breakdown('screen_resolution')[0]['key']);
        $this->assertSame('zh-CN', $svc->breakdown('browser_language')[0]['key']);
    }

    public function test_behavior_and_dimension_pages_render(): void
    {
        $user = User::where('email', 'm21@example.com')->firstOrFail();

        $v = $this->makeVisitor(['screen_resolution' => '1920x1080']);
        $s = $this->makeSession($v, ['total_events' => 2]);
        $this->makeEvent($v, $s, 'landing_page', '/', [
            'referrer_host' => 'www.baidu.com', 'referrer_path' => '/s?wd=monit',
        ]);
        $this->makeEvent($v, $s, 'pageview', '/pricing');

        $this->actingAs($user);

        $resp = $this->get(route('stats.behavior', $this->website->website_id));
        $resp->assertOk()->assertSee('24 小时时段分布');

        $this->get(route('stats.top_cities', $this->website->website_id))->assertOk();
        $this->get(route('stats.top_languages', $this->website->website_id))->assertOk();
        $this->get(route('stats.top_resolutions', $this->website->website_id))
            ->assertOk()->assertSee('1920x1080');
    }

    public function test_lightweight_behavior_is_safe(): void
    {
        $this->website->update(['tracking_type' => 'lightweight']);

        \App\Models\LightweightEvent::create([
            'website_id' => $this->website->website_id,
            'type' => 'landing_page', 'path' => '/lw',
            'referrer_host' => 'www.sogou.com', 'referrer_path' => '/web?query=laravel',
            'date' => now()->setTime(8, 0), 'expiration_date' => now()->addDays(365)->toDateString(),
        ]);

        $svc = StatisticsService::for($this->website)->lastDays(7);

        $this->assertSame(1, $svc->hourlySeries()[8]['pageviews']);
        $this->assertSame([], $svc->exitPages());

        $loyalty = $svc->loyalty();
        $this->assertSame(0, $loyalty['new_visitors']);

        $terms = $svc->searchTerms();
        $this->assertSame('laravel', $terms[0]['key'] ?? null);

        $channels = $svc->channels();
        $this->assertSame(1, $channels['organic']);
    }
}
