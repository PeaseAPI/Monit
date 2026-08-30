<?php

namespace Tests\Feature;

use App\Models\GoalConversion;
use App\Models\Website;
use App\Models\WebsiteGoal;
use App\Models\WebsiteVisitor;
use App\Models\VisitorSession;
use App\Services\StatisticsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * M16 配额与过滤器补齐（规格 §5.3 goal_id 过滤 / §10.2 dashboard_views_limit + sessions_replays_limit）
 */
class PlanQuotasAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------- §5.3 goal_id 过滤器 ---------------- */

    public function test_goal_id_filter_limits_to_converting_visitors(): void
    {
        $user = User::create([
            'name' => 'F', 'email' => 'f@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom', 'plan_settings' => ['sessions_events_limit' => -1],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_goal_f',
            'name' => 'GoalFilter', 'scheme' => 'https', 'host' => 'gf.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $goal = WebsiteGoal::create([
            'website_id' => $website->website_id, 'key' => 'signup',
            'type' => 'custom', 'name' => 'Signup', 'is_enabled' => true, 'datetime' => now(),
        ]);

        $converters = [];
        $convertersSessions = [];

        foreach ([0, 1] as $i) {
            $v = WebsiteVisitor::create([
                'website_id' => $website->website_id,
                'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
                'country_code' => 'CN', 'device_type' => 'desktop',
                'date' => now(), 'last_date' => now(),
            ]);
            $s = VisitorSession::create([
                'website_id' => $website->website_id, 'visitor_id' => $v->visitor_id,
                'session_uuid_binary' => Uuid::uuid4()->getBytes(),
                'date' => now(), 'total_events' => 0,
            ]);
            \App\Models\SessionEvent::create([
                'event_uuid_binary' => Uuid::uuid4()->getBytes(),
                'session_id' => $s->session_id, 'visitor_id' => $v->visitor_id,
                'website_id' => $website->website_id,
                'type' => 'landing_page', 'path' => '/p'.$i,
                'has_bounced' => false, 'date' => now(),
                'expiration_date' => now()->addDays(365),
            ]);
            $converters[$i] = $v;
            $convertersSessions[$i] = $s;
        }

        // 只有第一个访客转化了目标
        GoalConversion::create([
            'goal_id' => $goal->goal_id,
            'event_id' => null,
            'session_id' => $convertersSessions[0]->session_id,
            'visitor_id' => $converters[0]->visitor_id,
            'website_id' => $website->website_id,
            'datetime' => now(),
        ]);

        $svc = StatisticsService::for($website)->lastDays(7);

        $this->assertSame(2, $svc->overview()['visitors']);

        $filtered = (clone $svc)->filters(['goal_id' => (string) $goal->goal_id])->overview();
        $this->assertSame(1, $filtered['visitors']);
        $this->assertSame(1, $filtered['pageviews']);
    }

    /* ---------------- §10.2 dashboard_views_limit ---------------- */

    public function test_dashboard_views_limit_blocks_creation_over_quota(): void
    {
        $user = User::create([
            'name' => 'DV', 'email' => 'dv@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['dashboard_views_limit' => 1],
        ]);

        \App\Models\DashboardView::create([
            'user_id' => $user->user_id, 'name' => 'First', 'settings' => [], 'order' => 0, 'datetime' => now(),
        ]);

        $response = $this->actingAs($user)->post('/dashboard-views', [
            'name' => 'Second', 'settings' => ['a' => 1], 'datetime' => now(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('plan');
        $this->assertDatabaseCount('dashboard_views', 1);
    }

    public function test_dashboard_views_unlimited_when_minus_one(): void
    {
        $user = User::create([
            'name' => 'DV2', 'email' => 'dv2@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['dashboard_views_limit' => -1],
        ]);

        \App\Models\DashboardView::create([
            'user_id' => $user->user_id, 'name' => 'First', 'settings' => [], 'order' => 0, 'datetime' => now(),
        ]);

        $response = $this->actingAs($user)->post('/dashboard-views', [
            'name' => 'Second', 'settings' => ['a' => 1], 'datetime' => now(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('dashboard_views', 2);
    }

    /* ---------------- §10.2 sessions_replays_limit ---------------- */

    public function test_replay_chunk_skipped_when_replays_quota_exhausted(): void
    {
        $user = User::create([
            'name' => 'RP', 'email' => 'rp@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'sessions_replays_limit' => 1],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_rp_q',
            'name' => 'ReplayQuota', 'scheme' => 'https', 'host' => 'rq.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'sessions_replays_is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        config(['monit.pixel.events_retention_days' => 365, 'monit.pixel.replays_retention_days' => 30]);

        $visitorUuid = Uuid::uuid4()->toString();
        $sessionUuid = Uuid::uuid4()->toString();

        $payload = fn (string $type, array $data = []) => [
            'type' => $type,
            'url' => 'https://rq.test/',
            'visitor_uuid' => $visitorUuid,
            'visitor_session_uuid' => $sessionUuid,
            'visitor_session_event_uuid' => Uuid::uuid4()->toString(),
            'data' => $data,
        ];

        // 建立访客与会话
        $this->post('/pixel-track/px_rp_q', ['data' => json_encode($payload('initiate_visitor', ['resolution' => ['width' => 1440, 'height' => 900], 'timezone' => 'Asia/Shanghai', 'theme' => 'light']))])->assertStatus(204);
        $this->post('/pixel-track/px_rp_q', ['data' => json_encode($payload('landing_page', ['url' => 'https://rq.test/', 'title' => 'Home']))])->assertStatus(204);

        // 配额已耗尽（月度回放 1/1）
        $website->forceFill(['current_month_sessions_replays' => 1])->save();

        $this->post('/pixel-track/px_rp_q', ['data' => json_encode($payload('replays', ['chunk' => base64_encode('[]')]))])->assertStatus(204);

        $this->assertDatabaseCount('sessions_replays', 0);

        // 放宽配额后可写入
        $user->forceFill(['plan_settings' => ['sessions_events_limit' => -1, 'sessions_replays_limit' => -1]])->save();

        $this->post('/pixel-track/px_rp_q', ['data' => json_encode($payload('replays', ['chunk' => base64_encode('[]')]))])->assertStatus(204);

        $this->assertDatabaseCount('sessions_replays', 1);
        $this->assertSame(2, $website->fresh()->current_month_sessions_replays);
    }
}
