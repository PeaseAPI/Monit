<?php

namespace Tests\Feature;

use App\Models\Heatmap;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteGoal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * M2 像素采集协议端到端（规格 §4）：
 * ADV 全事件类型 + LW 分流 + 前置校验（host/IP/限额/禁用）+ 用量计数
 */
class PixelTrackTest extends TestCase
{
    use RefreshDatabase;

    protected Website $website;

    protected string $visitorUuid;

    protected string $sessionUuid;

    protected string $eventUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'websites_limit' => -1],
        ]);

        $this->website = Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_test_key_123',
            'name' => 'Test Site',
            'scheme' => 'https',
            'host' => 'example.com',
            'tracking_type' => 'advanced',
            'is_enabled' => true,
            'bot_exclusion_is_enabled' => false,
            'query_parameters_tracking_is_enabled' => true,
            'events_children_is_enabled' => true,
            'sessions_replays_is_enabled' => true,
            'websites_heatmaps_is_enabled' => true,
            'ip_tracking_is_enabled' => true,
            'excluded_ips' => '',
            'datetime' => now(),
        ]);

        $this->visitorUuid = Uuid::uuid4()->toString();
        $this->sessionUuid = Uuid::uuid4()->toString();
        $this->eventUuid = Uuid::uuid4()->toString();

        config(['monit.pixel.events_retention_days' => 365, 'monit.pixel.replays_retention_days' => 30]);
    }

    protected function track(array $payload, array $server = []): TestResponse
    {
        return $this->post('/pixel-track/px_test_key_123', ['data' => json_encode($payload)], $server);
    }

    protected function basePayload(string $type): array
    {
        return [
            'type' => $type,
            'url' => 'https://example.com/',
            'visitor_uuid' => $this->visitorUuid,
            'visitor_session_uuid' => $this->sessionUuid,
            'visitor_session_event_uuid' => $this->eventUuid,
        ];
    }

    public function test_full_advanced_lifecycle(): void
    {
        // 1. initiate_visitor
        $this->track(array_merge($this->basePayload('initiate_visitor'), [
            'data' => ['resolution' => ['width' => 1920, 'height' => 1080], 'timezone' => 'Asia/Shanghai', 'theme' => 'light'],
        ]))->assertStatus(204);

        $this->assertDatabaseCount('websites_visitors', 1);

        // 2. landing_page
        $this->track(array_merge($this->basePayload('landing_page'), [
            'data' => ['url' => 'https://example.com/?utm_source=google', 'title' => 'Home', 'referrer' => 'https://www.google.com/search', 'viewport' => ['width' => 1440, 'height' => 900]],
        ]))->assertStatus(204);

        $this->assertDatabaseCount('visitors_sessions', 1);
        $this->assertDatabaseCount('sessions_events', 1);
        $this->assertDatabaseHas('sessions_events', ['type' => 'landing_page', 'has_bounced' => true, 'utm_source' => 'google', 'referrer_host' => 'www.google.com']);

        // 3. pageview（跳出翻转为否）
        $this->eventUuid = Uuid::uuid4()->toString();
        $this->track(array_merge($this->basePayload('pageview'), [
            'data' => ['url' => 'https://example.com/pricing', 'title' => 'Pricing', 'viewport' => ['width' => 1440, 'height' => 900]],
        ]))->assertStatus(204);

        $this->assertDatabaseCount('sessions_events', 2);
        $this->assertDatabaseHas('sessions_events', ['type' => 'landing_page', 'has_bounced' => false]);

        // 4. click 子事件
        $this->track(array_merge($this->basePayload('click'), [
            'data' => ['selector' => '#cta'],
        ]))->assertStatus(204);

        $this->assertDatabaseHas('events_children', ['type' => 'click']);

        // 5. outbound_click
        $payload = $this->basePayload('outbound_click');
        $payload['outbound_url'] = 'https://external.org/offers';
        $payload['outbound_title'] = 'Offers';
        $this->track($payload)->assertStatus(204);

        $this->assertDatabaseHas('outbound_clicks', ['host' => 'external.org', 'path' => '/offers']);

        // 6. 用量计数：2 个 PV 事件 + click 子事件 + outbound_click 各 +1
        $this->website->refresh();
        $this->assertSame(4, (int) $this->website->current_month_sessions_events);
        $this->assertSame(2, (int) $this->website->last_24_hours_pageviews);
    }

    public function test_goal_conversion_deduplicates_per_visitor(): void
    {
        WebsiteGoal::create([
            'website_id' => $this->website->website_id,
            'key' => 'signup',
            'type' => 'custom',
            'name' => 'Signup',
            'is_enabled' => true,
        ]);

        $this->track(array_merge($this->basePayload('initiate_visitor'), ['data' => []]))->assertStatus(204);
        $this->track(array_merge($this->basePayload('landing_page'), ['data' => ['url' => 'https://example.com/']]))->assertStatus(204);

        $payload = array_merge($this->basePayload('goal_conversion'), ['goal_key' => 'signup']);

        $this->track($payload)->assertStatus(204);
        $this->assertDatabaseCount('goals_conversions', 1);

        // 同访客重复转化被拒绝
        $this->track($payload)->assertStatus(204);
        $this->assertDatabaseCount('goals_conversions', 1);
    }

    public function test_heatmap_lifecycle(): void
    {
        $heatmap = Heatmap::create([
            'website_id' => $this->website->website_id,
            'path' => '/',
            'name' => 'Home Heatmap',
            'is_enabled' => true,
            'datetime' => now(),
        ]);

        // 快照
        $this->track(array_merge($this->basePayload('heatmap_snapshot'), [
            'heatmap_id' => $heatmap->heatmap_id,
            'data' => ['type' => 'FullSnapshot', 'dom' => '<html>…</html>'],
        ]))->assertStatus(204);

        $heatmap->refresh();
        $this->assertNotNull($heatmap->snapshot_id_desktop);
        $this->assertGreaterThan(0, $heatmap->desktop_size);

        // 点击坐标
        $this->track(array_merge($this->basePayload('heatmap_snapshot_click'), [
            'heatmap_id' => $heatmap->heatmap_id,
            'x_normalized' => 42.5,
            'y_normalized' => 87.3,
            'count' => 3,
        ]))->assertStatus(204);

        $this->assertDatabaseCount('heatmap_snapshot_clicks', 1);

        // 滚动深度（按10取整）
        $this->track(array_merge($this->basePayload('heatmap_snapshot_scroll'), [
            'heatmap_id' => $heatmap->heatmap_id,
            'max_scroll' => 73,
        ]))->assertStatus(204);

        $this->assertDatabaseHas('heatmap_snapshot_scrolls', ['max_scroll' => 70]);

        // 同事件再上报更深滚动 → upsert 取更大值
        $this->track(array_merge($this->basePayload('heatmap_snapshot_scroll'), [
            'heatmap_id' => $heatmap->heatmap_id,
            'max_scroll' => 99,
        ]))->assertStatus(204);

        $this->assertDatabaseCount('heatmap_snapshot_scrolls', 1);
        $this->assertDatabaseHas('heatmap_snapshot_scrolls', ['max_scroll' => 100]);
    }

    public function test_lightweight_mode_writes_single_table(): void
    {
        $this->website->update(['tracking_type' => 'lightweight']);

        $this->track(array_merge($this->basePayload('landing_page'), [
            'data' => ['url' => 'https://example.com/lw?utm_source=news', 'title' => 'LW'],
        ]))->assertStatus(204);

        $this->assertDatabaseCount('lightweight_events', 1);
        $this->assertDatabaseHas('lightweight_events', ['type' => 'landing_page', 'utm_source' => 'news']);
        $this->assertDatabaseCount('sessions_events', 0);
    }

    public function test_precheck_host_mismatch_is_skipped(): void
    {
        $payload = $this->basePayload('initiate_visitor');
        $payload['url'] = 'https://evil.example.net/';
        $payload['data'] = [];

        $this->track($payload)->assertStatus(204);
        $this->assertDatabaseCount('websites_visitors', 0);
    }

    public function test_precheck_ip_exclusion_is_skipped(): void
    {
        $this->website->update(['excluded_ips' => '10.0.0.1']);

        $this->track(array_merge($this->basePayload('initiate_visitor'), ['data' => []]), [
            'REMOTE_ADDR' => '10.0.0.1',
        ])->assertStatus(204);

        $this->assertDatabaseCount('websites_visitors', 0);
    }

    public function test_precheck_plan_limit_blocks_events(): void
    {
        $user = $this->website->user;
        $user->plan_settings = ['sessions_events_limit' => 5];
        $user->save();
        $this->website->update(['current_month_sessions_events' => 5]);

        $this->track(array_merge($this->basePayload('landing_page'), [
            'data' => ['url' => 'https://example.com/'],
        ]))->assertStatus(204);

        // 事件被限额拦截，且限额通知标记已置位
        $this->assertDatabaseCount('sessions_events', 0);
        $this->assertTrue((bool) $this->website->refresh()->plan_sessions_events_limit_notice);
    }

    public function test_disabled_website_is_skipped(): void
    {
        $this->website->update(['is_enabled' => false]);

        $this->track(array_merge($this->basePayload('initiate_visitor'), ['data' => []]))->assertStatus(204);
        $this->assertDatabaseCount('websites_visitors', 0);
    }
}
