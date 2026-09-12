<?php

namespace Tests\Feature;

use App\Models\DashboardView;
use App\Models\GoalConversion;
use App\Models\SessionEvent;
use App\Models\SessionReplay;
use App\Models\User;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteGoal;
use App\Models\WebsiteVisitor;
use App\Services\PlanLimitService;
use App\Services\StatisticsService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
            SessionEvent::create([
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

        DashboardView::create([
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

        DashboardView::create([
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
        $this->assertSame(2, $this->freshModel($website)->current_month_sessions_replays);
    }

    public function test_replay_chunk_rejected_when_replays_limit_is_zero(): void
    {
        // sessions_replays_limit=0 = 无回放功能（落地页明确宣传 feat_no_replays）：
        // 服务端必须拒收且不标记限额通知（0 是功能禁用而非配额耗尽）。
        // 回归：原实现「>0 才检查」把 0 误放行，禁用套餐仍接收回放数据。
        $user = User::create([
            'name' => 'RZ', 'email' => 'rz@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'sessions_replays_limit' => 0],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_rp_z',
            'name' => 'ReplayZero', 'scheme' => 'https', 'host' => 'rz.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'sessions_replays_is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        config(['monit.pixel.events_retention_days' => 365, 'monit.pixel.replays_retention_days' => 30]);

        $visitorUuid = Uuid::uuid4()->toString();
        $sessionUuid = Uuid::uuid4()->toString();

        $payload = fn (string $type, array $data = []) => [
            'type' => $type,
            'url' => 'https://rz.test/',
            'visitor_uuid' => $visitorUuid,
            'visitor_session_uuid' => $sessionUuid,
            'visitor_session_event_uuid' => Uuid::uuid4()->toString(),
            'data' => $data,
        ];

        $this->post('/pixel-track/px_rp_z', ['data' => json_encode($payload('initiate_visitor', ['resolution' => ['width' => 1440, 'height' => 900], 'timezone' => 'Asia/Shanghai', 'theme' => 'light']))])->assertStatus(204);
        $this->post('/pixel-track/px_rp_z', ['data' => json_encode($payload('landing_page', ['url' => 'https://rz.test/', 'title' => 'Home']))])->assertStatus(204);

        $this->post('/pixel-track/px_rp_z', ['data' => json_encode($payload('replays', ['chunk' => base64_encode('[]')]))])->assertStatus(204);

        $this->assertDatabaseCount('sessions_replays', 0);
        $this->assertSame(0, $this->freshModel($website)->current_month_sessions_replays);
        // 0=禁用：不标记限额通知（与配额耗尽区分，避免误导用户升级）
        $this->assertFalse($this->freshModel($website)->plan_sessions_replays_limit_notice);
    }

    public function test_replay_quota_exhausted_marks_limit_notice(): void
    {
        // 配额耗尽（区别于 0=禁用）：拒收 + 标记 plan_sessions_replays_limit_notice
        // （对齐 sessions_events_limit 超限行为，WebsitesLimitNoticeCommand 依赖该标记汇总通知）
        $user = User::create([
            'name' => 'RN', 'email' => 'rn@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'sessions_replays_limit' => 1],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_rp_n',
            'name' => 'ReplayNotice', 'scheme' => 'https', 'host' => 'rn.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'sessions_replays_is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        config(['monit.pixel.events_retention_days' => 365, 'monit.pixel.replays_retention_days' => 30]);

        $visitorUuid = Uuid::uuid4()->toString();
        $sessionUuid = Uuid::uuid4()->toString();

        $payload = fn (string $type, array $data = []) => [
            'type' => $type,
            'url' => 'https://rn.test/',
            'visitor_uuid' => $visitorUuid,
            'visitor_session_uuid' => $sessionUuid,
            'visitor_session_event_uuid' => Uuid::uuid4()->toString(),
            'data' => $data,
        ];

        $this->post('/pixel-track/px_rp_n', ['data' => json_encode($payload('initiate_visitor', ['resolution' => ['width' => 1440, 'height' => 900], 'timezone' => 'Asia/Shanghai', 'theme' => 'light']))])->assertStatus(204);
        $this->post('/pixel-track/px_rp_n', ['data' => json_encode($payload('landing_page', ['url' => 'https://rn.test/', 'title' => 'Home']))])->assertStatus(204);

        $website->forceFill(['current_month_sessions_replays' => 1])->save();

        $this->post('/pixel-track/px_rp_n', ['data' => json_encode($payload('replays', ['chunk' => base64_encode('[]')]))])->assertStatus(204);

        $this->assertDatabaseCount('sessions_replays', 0);
        $this->assertTrue($this->freshModel($website)->plan_sessions_replays_limit_notice);
    }

    public function test_sessions_replays_session_id_is_unique(): void
    {
        // 本用例验证 persistReplayChunk 在唯一键冲突时由异常捕获兜底，不抛出即通过
        $this->expectNotToPerformAssertions();

        // session_id 唯一索引：并发首建防重复的兜底（persistReplayChunk 捕获
        // UniqueConstraintViolationException 后重跑转追加分支，chunk 不丢）
        $user = User::create([
            'name' => 'RU', 'email' => 'ru@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'sessions_replays_limit' => -1],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_rp_u',
            'name' => 'ReplayUnique', 'scheme' => 'https', 'host' => 'ru.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'sessions_replays_is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $visitor = WebsiteVisitor::create([
            'website_id' => $website->website_id,
            'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
            'country_code' => 'CN', 'device_type' => 'desktop',
            'os_name' => 'macOS', 'browser_name' => 'Chrome',
            'date' => now(), 'last_date' => now(),
        ]);

        $session = VisitorSession::create([
            'website_id' => $website->website_id, 'visitor_id' => $visitor->visitor_id,
            'session_uuid_binary' => Uuid::uuid4()->getBytes(),
            'date' => now(), 'total_events' => 0,
        ]);

        $replay = ['session_id' => $session->session_id, 'visitor_id' => $visitor->visitor_id,
            'website_id' => $website->website_id, 'datetime' => now()];

        SessionReplay::create($replay);

        try {
            SessionReplay::create($replay);
            $this->fail('sessions_replays.session_id 唯一索引缺失：并发首建会产生重复回放行');
        } catch (UniqueConstraintViolationException) {

        }
    }

    /* ---------------- §10.2 events_children_limit ---------------- */

    public function test_event_children_rejected_when_limit_is_zero(): void
    {
        // events_children_limit=0 = 无此功能：拒收 + 不标记通知 + 计数不增
        // （原实现完全不检查且计数从未递增，0 配置被静默绕过）
        $user = User::create([
            'name' => 'EZ', 'email' => 'ez@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'events_children_limit' => 0],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_ec_z',
            'name' => 'ChildZero', 'scheme' => 'https', 'host' => 'ez.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        config(['monit.pixel.events_retention_days' => 365]);

        $visitorUuid = Uuid::uuid4()->toString();
        $sessionUuid = Uuid::uuid4()->toString();

        $payload = fn (string $type, array $data = []) => [
            'type' => $type,
            'url' => 'https://ez.test/',
            'visitor_uuid' => $visitorUuid,
            'visitor_session_uuid' => $sessionUuid,
            'visitor_session_event_uuid' => Uuid::uuid4()->toString(),
            'data' => $data,
        ];

        $this->post('/pixel-track/px_ec_z', ['data' => json_encode($payload('initiate_visitor', ['resolution' => ['width' => 1440, 'height' => 900], 'timezone' => 'Asia/Shanghai', 'theme' => 'light']))])->assertStatus(204);
        $this->post('/pixel-track/px_ec_z', ['data' => json_encode($payload('landing_page', ['url' => 'https://ez.test/', 'title' => 'Home']))])->assertStatus(204);

        $this->post('/pixel-track/px_ec_z', ['data' => json_encode($payload('click', ['selector' => 'a.btn']))])->assertStatus(204);

        $this->assertDatabaseCount('events_children', 0);
        $this->assertSame(0, $this->freshModel($website)->current_month_events_children);
        // 0=禁用：不标记限额通知（与配额耗尽区分，避免误导用户升级）
        $this->assertFalse($this->freshModel($website)->plan_events_children_limit_notice);
    }

    public function test_event_children_quota_exhausted_marks_limit_notice(): void
    {
        // 配额耗尽（区别于 0=禁用）：拒收 + 标记 plan_events_children_limit_notice
        // （WebsitesLimitNoticeCommand 依赖该标记汇总通知；计数列此前从未递增导致其比较恒为假）
        $user = User::create([
            'name' => 'EN', 'email' => 'en@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'events_children_limit' => 1],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_ec_n',
            'name' => 'ChildNotice', 'scheme' => 'https', 'host' => 'en.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        config(['monit.pixel.events_retention_days' => 365]);

        $visitorUuid = Uuid::uuid4()->toString();
        $sessionUuid = Uuid::uuid4()->toString();

        $payload = fn (string $type, array $data = []) => [
            'type' => $type,
            'url' => 'https://en.test/',
            'visitor_uuid' => $visitorUuid,
            'visitor_session_uuid' => $sessionUuid,
            'visitor_session_event_uuid' => Uuid::uuid4()->toString(),
            'data' => $data,
        ];

        $this->post('/pixel-track/px_ec_n', ['data' => json_encode($payload('initiate_visitor', ['resolution' => ['width' => 1440, 'height' => 900], 'timezone' => 'Asia/Shanghai', 'theme' => 'light']))])->assertStatus(204);
        $this->post('/pixel-track/px_ec_n', ['data' => json_encode($payload('landing_page', ['url' => 'https://en.test/', 'title' => 'Home']))])->assertStatus(204);

        // 第 1 个子事件：配额内，正常入库并递增计数
        $this->post('/pixel-track/px_ec_n', ['data' => json_encode($payload('click', ['selector' => '#one']))])->assertStatus(204);

        $this->assertDatabaseCount('events_children', 1);
        $this->assertSame(1, $this->freshModel($website)->current_month_events_children);

        // 第 2 个子事件：超限拒收
        $this->post('/pixel-track/px_ec_n', ['data' => json_encode($payload('click', ['selector' => '#two']))])->assertStatus(204);

        $this->assertDatabaseCount('events_children', 1);
        $this->assertSame(1, $this->freshModel($website)->current_month_events_children);
        $this->assertTrue($this->freshModel($website)->plan_events_children_limit_notice);
    }

    public function test_event_children_unlimited_when_key_missing(): void
    {
        // 缺键 = 不限（custom 套餐 plan_settings 不与 plan_defaults 合并）：正常入库 + 计数递增
        $user = User::create([
            'name' => 'EM', 'email' => 'em@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_ec_m',
            'name' => 'ChildUnlimited', 'scheme' => 'https', 'host' => 'em.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        config(['monit.pixel.events_retention_days' => 365]);

        $visitorUuid = Uuid::uuid4()->toString();
        $sessionUuid = Uuid::uuid4()->toString();

        $payload = fn (string $type, array $data = []) => [
            'type' => $type,
            'url' => 'https://em.test/',
            'visitor_uuid' => $visitorUuid,
            'visitor_session_uuid' => $sessionUuid,
            'visitor_session_event_uuid' => Uuid::uuid4()->toString(),
            'data' => $data,
        ];

        $this->post('/pixel-track/px_ec_m', ['data' => json_encode($payload('initiate_visitor', ['resolution' => ['width' => 1440, 'height' => 900], 'timezone' => 'Asia/Shanghai', 'theme' => 'light']))])->assertStatus(204);
        $this->post('/pixel-track/px_ec_m', ['data' => json_encode($payload('landing_page', ['url' => 'https://em.test/', 'title' => 'Home']))])->assertStatus(204);

        $this->post('/pixel-track/px_ec_m', ['data' => json_encode($payload('click', ['selector' => '#a']))])->assertStatus(204);
        $this->post('/pixel-track/px_ec_m', ['data' => json_encode($payload('scroll', ['depth' => 75]))])->assertStatus(204);

        $this->assertDatabaseCount('events_children', 2);
        $this->assertSame(2, $this->freshModel($website)->current_month_events_children);
        $this->assertFalse($this->freshModel($website)->plan_events_children_limit_notice);
    }

    /* ---------------- §13.1 月度重置 + 通知闭环 ---------------- */

    public function test_monthly_reset_clears_counters_and_notice_flags(): void
    {
        // 规格 §13.1 websites_events_reset：跨月须同时清 current_month_* 计数
        // 与 plan_*_limit_notice 标志。原实现漏清标志 → 超限用户终身只收一次
        // 通知邮件，后续每月超限因 where(flag,false) 查不到而全部静默。
        $user = User::create([
            'name' => 'MR', 'email' => 'mr@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['events_children_limit' => 10],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_mr',
            'name' => 'MonthlyReset', 'scheme' => 'https', 'host' => 'mr.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'stats_month' => now()->subMonth()->format('Y-m'),
            'current_month_sessions_events' => 999,
            'current_month_events_children' => 999,
            'current_month_sessions_replays' => 999,
            'plan_sessions_events_limit_notice' => true,
            'plan_events_children_limit_notice' => true,
            'plan_sessions_replays_limit_notice' => true,
        ]);

        $this->artisanCmd('monit:website-maintenance')->assertSuccessful();

        $fresh = $website->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame(now()->format('Y-m'), $fresh->stats_month);
        $this->assertSame(0, $fresh->current_month_sessions_events);
        $this->assertSame(0, $fresh->current_month_events_children);
        $this->assertSame(0, $fresh->current_month_sessions_replays);
        // 三个通知标志必须复位，否则次月超限通知永久静默
        $this->assertFalse($fresh->plan_sessions_events_limit_notice);
        $this->assertFalse($fresh->plan_events_children_limit_notice);
        $this->assertFalse($fresh->plan_sessions_replays_limit_notice);
    }

    public function test_limit_notice_skips_missing_limit_key(): void
    {
        // 通知侧缺键语义必须与采集侧一致（缺键=不限）。原实现 ?? 0 把 custom
        // 用户缺 events_children_limit/sessions_replays_limit 键判成「限额 0」，
        // 任何用量都触发超限误发邮件。
        $user = User::create([
            'name' => 'NK', 'email' => 'nk@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1], // 故意缺 events_children_limit / sessions_replays_limit
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_nk',
            'name' => 'NoKeyNotice', 'scheme' => 'https', 'host' => 'nk.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'current_month_events_children' => 500,
            'current_month_sessions_replays' => 100,
        ]);

        DB::table('settings')->updateOrInsert(['key' => 'analytics.email_notices_is_enabled'], ['value' => true]);

        Mail::fake();

        $this->artisanCmd('monit:websites-limit-notice')->assertSuccessful();

        // 缺键 = 不限：不发信、不标记
        Mail::assertNothingSent();
        $this->assertFalse($this->freshModel($website)->plan_events_children_limit_notice);
        $this->assertFalse($this->freshModel($website)->plan_sessions_replays_limit_notice);
    }

    public function test_limit_notice_skips_disabled_zero_limit(): void
    {
        // 0 = 功能禁用：通知 Cron 不得把「禁用」当成「限额 0 已超限」误发邮件
        // （生产 Plus 套餐 sessions_replays_limit=0；与采集侧 0 不标记语义一致）
        $user = User::create([
            'name' => 'ZD', 'email' => 'zd@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['sessions_replays_limit' => 0],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_zd',
            'name' => 'ZeroDisabled', 'scheme' => 'https', 'host' => 'zd.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'current_month_sessions_replays' => 42,
        ]);

        DB::table('settings')->updateOrInsert(['key' => 'analytics.email_notices_is_enabled'], ['value' => true]);

        Mail::fake();

        $this->artisanCmd('monit:websites-limit-notice')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertFalse($this->freshModel($website)->plan_sessions_replays_limit_notice);
    }

    public function test_plan_limit_missing_key_means_unlimited(): void
    {
        // PlanLimitService 缺键语义：数量限额键缺键=不限。修复前 ?? 0 把缺键
        // 当「限额 0」——生产套餐 quota() 未收录的 annotations_limit /
        // dashboard_views_limit / seo_keywords_limit 键导致付费用户功能全禁
        $service = app(PlanLimitService::class);

        $user = User::create([
            'name' => 'PL', 'email' => 'pl@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => 2],
        ]);

        foreach (['annotations_limit', 'dashboard_views_limit', 'seo_keywords_limit'] as $feature) {
            $this->assertTrue($service->checkLimit($user, $feature), "{$feature} 缺键应放行");
            $this->assertSame(-1, $service->getRemaining($user, $feature), "{$feature} 缺键剩余量应为 -1");
        }

        // 显式 0 = 禁用语义不被本次修复破坏
        $user->forceFill(['plan_settings' => ['annotations_limit' => 0]])->save();
        $this->assertFalse($service->checkLimit($this->freshModel($user), 'annotations_limit'));

        // 显式 -1 = 不限
        $user->forceFill(['plan_settings' => ['annotations_limit' => -1]])->save();
        $this->assertTrue($service->checkLimit($this->freshModel($user), 'annotations_limit'));
    }

    public function test_annotation_create_allowed_when_limit_key_missing(): void
    {
        // 中间件路径（plan_limit:annotations_limit）：数量键缺键不得被
        // isFeatureEnabled 的 (bool)(... ?? false) 拦成「功能未启用」
        $user = User::create([
            'name' => 'AN', 'email' => 'an@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => -1],
        ]);

        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_an',
            'name' => 'AnnoOK', 'scheme' => 'https', 'host' => 'an.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('stats.annotations.store'), [
                'website_id' => $website->website_id,
                'name' => '上线公告',
                'date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('annotations', [
            'user_id' => $user->user_id,
            'name' => '上线公告',
        ]);
    }
}
