<?php

namespace Tests\Feature;

use App\Models\SessionEvent;
use App\Models\SessionReplay;
use App\Models\User;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * 访客详情（Advanced）：会话分组时间线 + 回放入口 + 会话上限提示
 * 关联：StatsController::visitorDetail（advanced 分支）、SessionReplay
 */
class VisitorDetailAdvancedTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Owner',
            'email' => 'owner-adv@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'websites_limit' => -1],
        ]);

        $this->website = Website::create([
            'user_id' => $this->user->user_id,
            'pixel_key' => 'px_advanced_detail',
            'name' => 'Advanced Site',
            'scheme' => 'https',
            'host' => 'advanced.test',
            'tracking_type' => 'advanced',
            'ip_tracking_is_enabled' => true,
            'is_enabled' => true,
            'datetime' => now(),
        ]);
    }

    protected function makeVisitor(): WebsiteVisitor
    {
        return WebsiteVisitor::create([
            'website_id' => $this->website->website_id,
            'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
            'country_code' => 'CN',
            'region_name' => 'Guangdong',
            'city_name' => 'Shenzhen',
            'ip' => '203.0.113.7',
            'device_type' => 'desktop',
            'os_name' => 'macOS',
            'browser_name' => 'Chrome',
            'screen_resolution' => '2560x1440',
            'browser_language' => 'zh-CN',
            'date' => now()->subDay(),
            'last_date' => now(),
        ]);
    }

    protected function makeSession(WebsiteVisitor $visitor, mixed $date): VisitorSession
    {
        return VisitorSession::create([
            'website_id' => $this->website->website_id,
            'visitor_id' => $visitor->visitor_id,
            'session_uuid_binary' => Uuid::uuid4()->getBytes(),
            'date' => $date,
            'total_events' => 0,
        ]);
    }

    protected function makeEvent(WebsiteVisitor $v, VisitorSession $s, string $type, string $path, mixed $date, ?string $referrer = null): SessionEvent
    {
        return SessionEvent::create([
            'event_uuid_binary' => Uuid::uuid4()->getBytes(),
            'session_id' => $s->session_id,
            'visitor_id' => $v->visitor_id,
            'website_id' => $this->website->website_id,
            'type' => $type,
            'path' => $path,
            'referrer_host' => $referrer,
            'has_bounced' => false,
            'date' => $date,
            'expiration_date' => now()->addDays(365),
        ]);
    }

    public function test_advanced_detail_renders_sessions_replay_and_dwell(): void
    {
        $visitor = $this->makeVisitor();

        // 昨天的单事件会话（无回放）
        $old = $this->makeSession($visitor, now()->subDay());
        $this->makeEvent($visitor, $old, 'pageview', '/docs', now()->subDay());

        // 今天的会话：landing → 90s 后 pageview（时长/停留徽标均为 1m 30s），绑定回放
        $today = $this->makeSession($visitor, now());
        $landingAt = now()->subMinutes(10);
        $this->makeEvent($visitor, $today, 'landing_page', '/', $landingAt, 'www.google.com');
        $this->makeEvent($visitor, $today, 'pageview', '/pricing', $landingAt->copy()->addSeconds(90));

        $replay = SessionReplay::create([
            'session_id' => $today->session_id,
            'visitor_id' => $visitor->visitor_id,
            'website_id' => $this->website->website_id,
            'user_id' => $this->user->user_id,
            'events' => 0,
            'size' => 0,
            'data' => gzencode('[]'),
            'is_offloaded' => false,
            'is_too_short' => false,
            'datetime' => now(),
            'last_datetime' => now(),
            'expiration_date' => now()->addDays(365),
        ]);

        $html = (string) $this->actingAs($this->user)
            ->get(route('stats.visitor', ['website' => $this->website->website_id, 'visitorId' => $visitor->visitor_id]))
            ->assertOk()
            ->getContent();

        // 画像卡：标签、IP（ip_tracking_is_enabled）、设备、首次来源
        $this->assertStringContainsString('#'.$visitor->visitor_id, $html);
        $this->assertStringContainsString('203.0.113.7', $html);
        $this->assertStringContainsString('macOS / Chrome', $html);
        $this->assertStringContainsString('www.google.com', $html);

        // 会话头：时长徽标 + 仅绑定回放的会话有回放按钮
        $this->assertStringContainsString('1m 30s', $html);
        $this->assertStringContainsString(route('stats.replays.show', [$this->website->website_id, $replay->replay_id]), $html);

        // 事件行：路径 + 相邻停留徽标（+1m 30s）
        $this->assertStringContainsString('/pricing', $html);
        $this->assertStringContainsString('+1m 30s', $html);

        // 两个会话（今天 + 昨天）都渲染
        $this->assertStringContainsString('/docs', $html);
    }

    public function test_advanced_detail_caps_sessions_at_ten(): void
    {
        $visitor = $this->makeVisitor();

        // 12 个会话（i=12 最旧 → i=1 最新），每会话一条唯一路径事件
        foreach (range(12, 1) as $i) {
            $session = $this->makeSession($visitor, now()->subMinutes($i));
            $this->makeEvent($visitor, $session, 'pageview', '/s/'.$i, now()->subMinutes($i));
        }

        $html = (string) $this->actingAs($this->user)
            ->get(route('stats.visitor', ['website' => $this->website->website_id, 'visitorId' => $visitor->visitor_id]))
            ->assertOk()
            ->getContent();

        // 最近 10 个会话保留；最旧的第 11 个被裁剪
        $this->assertStringContainsString('/s/10', $html);
        $this->assertStringNotContainsString('/s/11', $html);

        // 最旧的第 12 个会话不渲染时间线，但其事件仍是全期进入路径（画像卡可见）
        $this->assertStringContainsString('/s/12', $html);

        // 超出上限的提示（展示 10 / 共 12）
        $this->assertStringContainsString(__('stats.visitor_sessions_capped', ['count' => 10]), $html);
    }
}
