<?php

namespace Tests\Feature;

use App\Models\Heatmap;
use App\Models\SessionReplay;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteGoal;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $server
     * @return TestResponse<Response>
     */
    protected function track(array $payload, array $server = []): TestResponse
    {
        return $this->post('/pixel-track/px_test_key_123', ['data' => json_encode($payload)], $server);
    }

    /**
     * @return array<string, mixed>
     */
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
        $this->assertSame(4, $this->website->current_month_sessions_events);
        $this->assertSame(2, $this->website->last_24_hours_pageviews);
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

        // 快照（模拟 rrweb Meta+FullSnapshot 事件对）
        $this->track(array_merge($this->basePayload('heatmap_snapshot'), [
            'heatmap_id' => $heatmap->heatmap_id,
            'data' => [
                'events' => [
                    ['type' => 4, 'data' => ['href' => 'https://example.com/']],
                    ['type' => 2, 'data' => ['node' => ['type' => 0, 'childNodes' => []]]],
                ],
                'viewport' => ['width' => 1920, 'height' => 1080],
            ],
        ]))->assertStatus(204);

        $heatmap->refresh();
        $this->assertNotNull($heatmap->snapshot_id_desktop);
        $this->assertGreaterThan(0, $heatmap->desktop_size);

        // 验证快照数据可被正确读取和解压
        /** @var object{data: string}|null $snapshotRow */
        $snapshotRow = DB::selectOne(
            'SELECT data FROM heatmaps_snapshots WHERE snapshot_id = ?',
            [$heatmap->snapshot_id_desktop],
        );
        $this->assertNotNull($snapshotRow);

        $decompressed = @gzdecode($snapshotRow->data);
        $this->assertNotFalse($decompressed, 'Snapshot data should be valid gzencode compressed');
        $snapshotData = $this->decodeJson($decompressed);

        $this->assertArrayHasKey('events', $snapshotData);

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

    /**
     * heatmap_check pathname 回退：热图通常按纯路径（/about）配置，而 PIXEL 上报的
     * path 是 pathname + search——带 ?utm_source=... 的访问也必须匹配到底图热图，
     * 否则该热图永远等不到底图快照（用户反馈「暂无热图/无底图」根因之一）
     */
    public function test_heatmap_check_matches_pure_path_heatmap_despite_query_string(): void
    {
        $heatmap = Heatmap::create([
            'website_id' => $this->website->website_id,
            'path' => '/about',
            'name' => 'About Heatmap',
            'is_enabled' => true,
            'datetime' => now(),
        ]);

        // 精确匹配路径正常返回
        $r1 = $this->get('/pixel-track/px_test_key_123?action=heatmap_check&path='.urlencode('/about'));
        $r1->assertStatus(200);
        $this->assertSame($heatmap->heatmap_id, $this->decodeJson((string) $r1->getContent())['heatmap_id']);

        // 带 query 的访问路径 → pathname 回退匹配到纯路径热图
        $r2 = $this->get('/pixel-track/px_test_key_123?action=heatmap_check&path='.urlencode('/about?utm_source=newsletter'));
        $r2->assertStatus(200);
        $this->assertSame($heatmap->heatmap_id, $this->decodeJson((string) $r2->getContent())['heatmap_id']);

        // 无匹配路径 → 响应不含 heatmap_id 键
        $r3 = $this->get('/pixel-track/px_test_key_123?action=heatmap_check&path='.urlencode('/missing?x=1'));
        $r3->assertStatus(200);
        $this->assertArrayNotHasKey('heatmap_id', $this->decodeJson((string) $r3->getContent()));
    }

    /**
     * heatmap_check replay_enabled 语义（回放停止增长的可观测闭环）：
     * 全局/网站开关开启时，套餐 sessions_replays_limit 缺键（-1 不限）→ true；
     * 显式 0（= 功能禁用，统一配额语义）→ false，客户端因此不启动 rrweb 录制
     */
    public function test_heatmap_check_replay_enabled_respects_plan_quota_zero(): void
    {
        Settings::set('analytics.sessions_replays_is_enabled', 'true');

        // 缺键 = 不限 → 启用
        $r1 = $this->get('/pixel-track/px_test_key_123?action=heatmap_check&path=/');
        $r1->assertStatus(200);
        $this->assertTrue($this->decodeJson((string) $r1->getContent())['replay_enabled']);

        // 显式 0 = 禁用
        $planUser = $this->website->user;
        $this->assertNotNull($planUser);
        $planUser->forceFill([
            'plan_settings' => ['sessions_events_limit' => -1, 'sessions_replays_limit' => 0],
        ])->save();

        $r2 = $this->get('/pixel-track/px_test_key_123?action=heatmap_check&path=/');
        $r2->assertStatus(200);
        $this->assertFalse($this->decodeJson((string) $r2->getContent())['replay_enabled']);
    }

    public function test_replay_data_stored_in_db(): void
    {
        // 1. 创建 visitor + session
        $this->track(array_merge($this->basePayload('initiate_visitor'), [
            'data' => ['resolution' => ['width' => 1920, 'height' => 1080], 'timezone' => 'UTC'],
        ]))->assertStatus(204);

        $this->track(array_merge($this->basePayload('landing_page'), [
            'data' => ['url' => 'https://example.com/', 'title' => 'Home'],
        ]))->assertStatus(204);

        // 2. 发送回放事件数据
        $replayEvents = [
            ['type' => 4, 'data' => ['href' => 'https://example.com/'], 'timestamp' => 1000],
            ['type' => 2, 'data' => ['node' => ['type' => 0]], 'timestamp' => 1001],
            ['type' => 3, 'data' => ['source' => 0], 'timestamp' => 1500],
        ];

        $this->track(array_merge($this->basePayload('replays'), [
            'data' => ['events' => $replayEvents],
        ]))->assertStatus(204);

        // 3. 验证 SessionReplay 记录已创建，events 和 size 字段非空
        $this->assertDatabaseCount('sessions_replays', 1);

        $replay = SessionReplay::first();
        $this->assertNotNull($replay);
        $this->assertEquals(3, $replay->events);
        $this->assertGreaterThan(0, $replay->size);

        // 4. 验证 data 列（LONGBLOB）有压缩数据，可被正确解压
        /** @var object{data: string}|null $row */
        $row = DB::selectOne(
            'SELECT data FROM sessions_replays WHERE replay_id = ?',
            [$replay->replay_id],
        );
        $this->assertNotNull($row);

        $decompressed = @gzdecode($row->data);
        $this->assertNotFalse($decompressed, 'Replay data should be valid gzencode compressed');
        $storedEvents = $this->decodeJson($decompressed);

        // 数据格式为事件数组
        $this->assertCount(3, $storedEvents);

        // 5. 发送第二批事件 → 应追加到已有数据
        $moreEvents = [
            ['type' => 3, 'data' => ['source' => 1], 'timestamp' => 2000],
            ['type' => 3, 'data' => ['source' => 2], 'timestamp' => 2500],
        ];

        $this->track(array_merge($this->basePayload('replays'), [
            'data' => ['events' => $moreEvents],
        ]))->assertStatus(204);

        $replay->refresh();
        $this->assertEquals(5, $replay->events);

        /** @var object{data: string}|null $row */
        $row = DB::selectOne(
            'SELECT data FROM sessions_replays WHERE replay_id = ?',
            [$replay->replay_id],
        );
        $this->assertNotNull($row);
        $decompressed = @(string) gzdecode($row->data);
        $allEvents = $this->decodeJson($decompressed);
        $this->assertCount(5, $allEvents);
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
        $this->assertNotNull($user);
        $user->plan_settings = ['sessions_events_limit' => 5];
        $user->save();
        $this->website->update(['current_month_sessions_events' => 5]);

        $this->track(array_merge($this->basePayload('landing_page'), [
            'data' => ['url' => 'https://example.com/'],
        ]))->assertStatus(204);

        // 事件被限额拦截，且限额通知标记已置位
        $this->assertDatabaseCount('sessions_events', 0);
        $this->assertTrue($this->website->refresh()->plan_sessions_events_limit_notice);
    }

    public function test_disabled_website_is_skipped(): void
    {
        $this->website->update(['is_enabled' => false]);

        $this->track(array_merge($this->basePayload('initiate_visitor'), ['data' => []]))->assertStatus(204);
        $this->assertDatabaseCount('websites_visitors', 0);
    }
}
