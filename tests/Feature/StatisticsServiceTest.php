<?php

namespace Tests\Feature;

use App\Models\LightweightEvent;
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
 * M3 统计指标（规格 §5）：overview / realtime / breakdown / §5.3 AnalyticsFilters
 */
class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Stats', 'email' => 'stats@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1],
        ]);

        $this->website = Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_stats', 'name' => 'Stats Site',
            'scheme' => 'https', 'host' => 'stats.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);
    }

    protected function makeVisitor(string $country = 'CN'): WebsiteVisitor
    {
        return WebsiteVisitor::create([
            'website_id' => $this->website->website_id,
            'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
            'country_code' => $country, 'device_type' => 'desktop',
            'os_name' => 'macOS', 'browser_name' => 'Chrome',
            'date' => now(), 'last_date' => now(),
        ]);
    }

    protected function makeSession(WebsiteVisitor $visitor): VisitorSession
    {
        return VisitorSession::create([
            'website_id' => $this->website->website_id,
            'visitor_id' => $visitor->visitor_id,
            'session_uuid_binary' => Uuid::uuid4()->getBytes(),
            'date' => now(), 'total_events' => 0,
        ]);
    }

    protected function makeEvent(WebsiteVisitor $v, VisitorSession $s, string $type, string $path, bool $bounced = false): SessionEvent
    {
        return SessionEvent::create([
            'event_uuid_binary' => Uuid::uuid4()->getBytes(),
            'session_id' => $s->session_id, 'visitor_id' => $v->visitor_id,
            'website_id' => $this->website->website_id,
            'type' => $type, 'path' => $path,
            'has_bounced' => $bounced, 'date' => now(),
            'expiration_date' => now()->addDays(365),
        ]);
    }

    public function test_overview_metrics(): void
    {
        $v1 = $this->makeVisitor('CN');
        $v2 = $this->makeVisitor('US');
        $s1 = $this->makeSession($v1);
        $s2 = $this->makeSession($v2);

        // v1: landing(pageview 后已翻转为未跳出) + pageview；v2: landing(跳出)
        $this->makeEvent($v1, $s1, 'landing_page', '/', false);
        $this->makeEvent($v1, $s1, 'pageview', '/pricing', false);
        $this->makeEvent($v2, $s2, 'landing_page', '/blog/post-1', true);

        $o = StatisticsService::for($this->website)->lastDays(7)->overview();

        $this->assertSame(3, $o['pageviews']);
        $this->assertSame(2, $o['visitors']);
        $this->assertSame(2, $o['sessions']);
        $this->assertSame(50.0, (float) $o['bounce_rate']);
    }

    public function test_breakdown_groups_and_orders(): void
    {
        $v1 = $this->makeVisitor('CN');
        $v2 = $this->makeVisitor('US');
        $s1 = $this->makeSession($v1);
        $s2 = $this->makeSession($v2);

        $this->makeEvent($v1, $s1, 'landing_page', '/blog/a');
        $this->makeEvent($v1, $s1, 'pageview', '/blog/b');
        $this->makeEvent($v2, $s2, 'landing_page', '/docs');

        $paths = StatisticsService::for($this->website)->lastDays(7)->breakdown('path');
        // 相同计数时顺序不保证，断言全集与计数
        $this->assertEqualsCanonicalizing(
            ['/blog/a', '/blog/b', '/docs'],
            array_column($paths, 'key')
        );
        $this->assertSame(1, $paths[0]['count']);

        $countries = StatisticsService::for($this->website)->lastDays(7)->breakdown('country_code');
        $this->assertCount(2, $countries);
        $this->assertEqualsCanonicalizing(['CN', 'US'], array_column($countries, 'key'));
    }

    public function test_analytics_filters_prefix_and_exact(): void
    {
        $v1 = $this->makeVisitor('CN');
        $v2 = $this->makeVisitor('US');
        $s1 = $this->makeSession($v1);
        $s2 = $this->makeSession($v2);

        $this->makeEvent($v1, $s1, 'landing_page', '/blog/a');
        $this->makeEvent($v2, $s2, 'landing_page', '/docs');

        $svc = StatisticsService::for($this->website)->lastDays(7);

        // path 前缀过滤：仅 /blog/*
        $filtered = (clone $svc)->filters(['path' => '/blog'])->overview();
        $this->assertSame(1, $filtered['pageviews']);

        // 访客维度精确过滤：country_code=US
        $byCountry = (clone $svc)->filters(['country_code' => 'US'])->overview();
        $this->assertSame(1, $byCountry['pageviews']);

        // 组合：path=/docs + country=CN（无交集）
        $none = (clone $svc)->filters(['path' => '/docs', 'country_code' => 'CN'])->overview();
        $this->assertSame(0, $none['pageviews']);
    }

    public function test_lightweight_mode_overview_with_filter(): void
    {
        $this->website->update(['tracking_type' => 'lightweight']);

        foreach (['/a', '/b'] as $path) {
            LightweightEvent::create([
                'website_id' => $this->website->website_id,
                'type' => 'landing_page', 'path' => $path,
                'date' => now(), 'expiration_date' => now()->addDays(365)->toDateString(),
            ]);
        }

        $o = StatisticsService::for($this->website)->lastDays(7)->overview();
        $this->assertSame(2, $o['pageviews']);

        $f = StatisticsService::for($this->website)->lastDays(7)->filters(['path' => '/a'])->overview();
        $this->assertSame(1, $f['pageviews']);
    }

    public function test_realtime_counts_distinct_visitors(): void
    {
        $v1 = $this->makeVisitor();
        $v2 = $this->makeVisitor();
        $s1 = $this->makeSession($v1);
        $s2 = $this->makeSession($v2);

        $this->makeEvent($v1, $s1, 'landing_page', '/');
        $this->makeEvent($v1, $s1, 'pageview', '/x');
        $this->makeEvent($v2, $s2, 'landing_page', '/');

        $this->assertSame(2, StatisticsService::for($this->website)->realtime());
    }
}
