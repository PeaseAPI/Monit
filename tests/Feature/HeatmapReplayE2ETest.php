<?php

namespace Tests\Feature;

use App\Models\Heatmap;
use App\Models\HeatmapSnapshotClick;
use App\Models\HeatmapSnapshotScroll;
use App\Models\SessionReplay;
use App\Models\User;
use App\Models\Website;
use App\Support\Settings;
use App\Support\Typed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A5/A6：热图与回放端到端链路（采集 → 存储 → 用户端渲染读取）
 * 模拟访问者端 pixel 上报全流程，验证「热图截图标注 / 回放录像」真实可用
 */
class HeatmapReplayE2ETest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'E2E 用户', 'email' => uniqid('e2e-').'@heat.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ]);

        $this->website = Website::create([
            'user_id' => $this->user->user_id,
            'pixel_key' => 'px_e2e_'.Str::random(8),
            'name' => 'E2E Site',
            'scheme' => 'https',
            'host' => 'example.com',
            'tracking_type' => 'advanced',
            'is_enabled' => true,
            'bot_exclusion_is_enabled' => false,
            'query_parameters_tracking_is_enabled' => true,
            'sessions_replays_is_enabled' => true,
            'websites_heatmaps_is_enabled' => true,
            'excluded_ips' => '',
            'datetime' => now(),
        ]);

        // 全局热图/回放开关（后台 设置 → 分析）
        Settings::set('analytics.websites_heatmaps_is_enabled', 'true');
        Settings::set('analytics.sessions_replays_is_enabled', 'true');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function report(array $payload): void
    {
        $this->post('/pixel-track/'.$this->website->pixel_key, [
            'data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ])->assertStatus(204);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rrwebSnapshotPayload(int $heatmapId): array
    {
        return [
            'type' => 'heatmap_snapshot',
            'heatmap_id' => $heatmapId,
            'visitor_uuid' => Str::uuid()->toString(),
            'url' => 'https://example.com/',
            'data' => [
                'viewport' => ['width' => 1920, 'height' => 1080],
                'events' => [
                    ['type' => 4, 'data' => ['href' => 'https://example.com/', 'width' => 1920, 'height' => 1080]],
                    ['type' => 2, 'data' => ['node' => ['type' => 0, 'childNodes' => []]]],
                ],
            ],
        ];
    }

    public function test_heatmap_check_auto_creates_heatmap_and_snapshot_pipeline_works(): void
    {
        // 1. 访问者命中页面 → heatmap_check 自动创建热图
        $check = $this->get('/pixel-track/'.$this->website->pixel_key.'?action=heatmap_check&path=/');
        $check->assertOk()->assertJsonStructure(['heatmap_id', 'replay_enabled']);
        $heatmapId = Typed::int($check->json('heatmap_id'));
        $this->assertTrue(Heatmap::where('website_id', $this->website->website_id)->where('path', '/')->exists());

        // 2. 访问者端 rrweb 快照上报 → LONGBLOB 存储
        $this->report($this->rrwebSnapshotPayload($heatmapId));

        $heatmap = Heatmap::findOrFail($heatmapId);
        $this->assertNotNull($heatmap->snapshot_id_desktop);
        /** @var object{data: string}|null $row */
        $row = DB::selectOne('SELECT data FROM heatmaps_snapshots WHERE snapshot_id = ?', [$heatmap->snapshot_id_desktop]);
        $this->assertNotNull($row);

        $decoded = $this->decodeJson((string) gzdecode($row->data));
        $this->assertIsArray($decoded['events']);
        $this->assertCount(2, $decoded['events']);

        // 3. 点击 + 滚动坐标上报 → 归一化坐标存储
        //    （scroll 以 visitor_session_event_uuid 为去重键，click 用 x/y）
        $this->report([
            'type' => 'heatmap_snapshot_click', 'heatmap_id' => $heatmapId,
            'visitor_uuid' => Str::uuid()->toString(), 'url' => 'https://example.com/',
            'x_normalized' => 42.5, 'y_normalized' => 87.5, 'count' => 3,
        ]);
        $this->report([
            'type' => 'heatmap_snapshot_scroll', 'heatmap_id' => $heatmapId,
            'visitor_uuid' => Str::uuid()->toString(), 'url' => 'https://example.com/',
            'visitor_session_event_uuid' => Str::uuid()->toString(),
            'max_scroll' => 72,
        ]);

        $this->assertSame(1, HeatmapSnapshotClick::where('website_id', $this->website->website_id)->count());
        $this->assertSame(1, HeatmapSnapshotScroll::where('website_id', $this->website->website_id)->count());  // 4. 用户端：热图详情页渲染 + 快照端点返回 rrweb 事件 + AJAX 返回坐标 $this->actingAs($this->user) ->get('/stats/'. Typed::string($this->website->website_id).'/heatmaps/'.$heatmapId) ->assertOk();

        $snapshot = $this->actingAs($this->user)
            ->get('/stats/'.Typed::string($this->website->website_id).'/heatmaps/'.Typed::string($heatmapId).'/snapshot?device=desktop');
        $snapshot->assertOk();
        $this->assertIsArray($snapshot->json('events'));
        $this->assertCount(2, $snapshot->json('events'));

        $ajax = $this->actingAs($this->user)
            ->get('/stats/'.Typed::string($this->website->website_id).'/heatmaps-ajax/'.Typed::string($heatmapId));
        $ajax->assertOk()->assertJsonCount(1, 'clicks')->assertJsonCount(1, 'scrolls');
    }

    public function test_replay_chunk_pipeline_stores_and_serves_events(): void
    {
        $sessionUuid = Str::uuid()->toString();
        $events = [
            ['type' => 4, 'data' => ['href' => 'https://example.com/']],
            ['type' => 2, 'data' => ['node' => ['type' => 0]]],
            ['type' => 3, 'data' => ['source' => 2, 'positions' => []]],
        ];

        // 1. 回放 chunk 上报（访问者端 rrweb.record 产出）
        $this->report([
            'type' => 'replays',
            'visitor_uuid' => Str::uuid()->toString(),
            'visitor_session_uuid' => $sessionUuid,
            'url' => 'https://example.com/',
            'data' => ['events' => $events],
        ]);

        $replay = SessionReplay::where('website_id', $this->website->website_id)->first();
        $this->assertNotNull($replay, '回放主记录应已创建');

        /** @var object{data: string}|null $row */
        $row = DB::selectOne('SELECT data FROM sessions_replays WHERE replay_id = ?', [$replay->replay_id]);
        $this->assertNotNull($row);
        $stored = $this->decodeJson((string) gzdecode($row->data));
        // 存储结构兼容两种：{events: [...]}（新）或直接事件数组（旧合并路径）
        $storedEvents = $stored['events'] ?? $stored;
        $this->assertIsArray($storedEvents);
        $this->assertCount(3, $storedEvents);

        // 2. 用户端：回放列表 + 详情页 + 事件端点（rrweb-player 消费）
        $this->actingAs($this->user)
            ->get('/stats/'.$this->website->website_id.'/replays')
            ->assertOk();

        $this->actingAs($this->user)
            ->get('/stats/'.$this->website->website_id.'/replays/'.$replay->replay_id)
            ->assertOk();

        $eventsResponse = $this->actingAs($this->user)
            ->get('/stats/'.$this->website->website_id.'/replays/'.$replay->replay_id.'/events');
        $eventsResponse->assertOk();
        $this->assertIsArray($eventsResponse->json());
        $this->assertCount(3, $eventsResponse->json());
    }

    public function test_heatmap_check_replays_disabled_without_settings(): void
    {
        Settings::set('analytics.sessions_replays_is_enabled', 'false');

        $check = $this->get('/pixel-track/'.$this->website->pixel_key.'?action=heatmap_check&path=/page-a');
        $check->assertOk();
        $this->assertFalse($check->json('replay_enabled'));
    }
}
