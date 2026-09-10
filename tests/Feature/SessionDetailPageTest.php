<?php

namespace Tests\Feature;

use App\Models\SessionEvent;
use App\Models\User;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * 全量巡检修复：GET /stats/{website}/sessions/{sessionId}（stats.session）
 * 此前 StatsController::sessionDetail 引用视图 stats.session 但文件缺失 → 访问必 500。
 */
class SessionDetailPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_detail_page_renders_for_owner(): void
    {
        $owner = User::create([
            'name' => 'Owner', 'email' => 'sess-page@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $website = Website::create([
            'user_id' => $owner->user_id,
            'pixel_key' => 'px_sesspage', 'name' => 'Sess Page Site', 'scheme' => 'https',
            'host' => 'sesspage.test', 'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);
        $visitor = WebsiteVisitor::create([
            'website_id' => $website->website_id,
            'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
            'country_code' => 'CN', 'city_name' => 'Hangzhou', 'device_type' => 'desktop',
            'os_name' => 'macOS', 'browser_name' => 'Chrome', 'screen_resolution' => '1920x1080',
            'browser_language' => 'zh-CN',
            'date' => now(), 'last_date' => now(),
        ]);
        $session = VisitorSession::create([
            'website_id' => $website->website_id,
            'visitor_id' => $visitor->visitor_id,
            'session_uuid_binary' => Uuid::uuid4()->getBytes(),
            'date' => now(), 'total_events' => 1,
        ]);
        SessionEvent::create([
            'event_uuid_binary' => Uuid::uuid4()->getBytes(),
            'session_id' => $session->session_id,
            'visitor_id' => $visitor->visitor_id,
            'website_id' => $website->website_id,
            'type' => 'pageview', 'path' => '/pricing', 'title' => 'Pricing',
            'referrer_host' => 'google.com', 'utm_source' => 'google',
            'date' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('stats.session', [$website, $session->session_id]))
            ->assertOk()
            ->assertSee('/pricing', false)
            ->assertSee($session->session_uuid, false);
    }

    public function test_session_detail_denied_for_other_users(): void
    {
        $owner = User::create([
            'name' => 'Owner2', 'email' => 'sess-page2@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $website = Website::create([
            'user_id' => $owner->user_id,
            'pixel_key' => 'px_sesspage2', 'name' => 'Sess Page 2', 'scheme' => 'https',
            'host' => 'sesspage2.test', 'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);
        $session = VisitorSession::create([
            'website_id' => $website->website_id,
            'visitor_id' => WebsiteVisitor::create([
                'website_id' => $website->website_id,
                'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
                'date' => now(), 'last_date' => now(),
            ])->visitor_id,
            'session_uuid_binary' => Uuid::uuid4()->getBytes(),
            'date' => now(), 'total_events' => 0,
        ]);
        $intruder = User::create([
            'name' => 'Intruder', 'email' => 'sess-page-x@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $this->actingAs($intruder)
            ->get(route('stats.session', [$website, $session->session_id]))
            ->assertForbidden();
    }
}
