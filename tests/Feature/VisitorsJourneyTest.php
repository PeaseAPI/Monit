<?php

namespace Tests\Feature;

use App\Models\LightweightEvent;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * 访客明细与旅程：lightweight visitor_uuid 落库 → 列表聚合 → 旅程时间线
 * 关联：PixelTracker（uuid 写入）、StatisticsService::topVisitors、StatsController::visitorDetail
 */
class VisitorsJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected Website $website;

    protected User $user;

    protected string $visitorUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'websites_limit' => -1],
        ]);

        $this->website = Website::create([
            'user_id' => $this->user->user_id,
            'pixel_key' => 'px_journey_key',
            'name' => 'Journey Site',
            'scheme' => 'https',
            'host' => 'journey.test',
            'tracking_type' => 'lightweight',
            'is_enabled' => true,
            'bot_exclusion_is_enabled' => false,
            'query_parameters_tracking_is_enabled' => false,
            'datetime' => now(),
        ]);

        $this->visitorUuid = Uuid::uuid4()->toString();
    }

    protected function track(string $url, ?string $referrer = null): void
    {
        $payload = [
            'type' => 'pageview',
            'visitor_uuid' => $this->visitorUuid,
            'data' => array_filter(['url' => $url, 'referrer' => $referrer]),
        ];

        $this->postJson("/pixel-track/{$this->website->pixel_key}", ['data' => json_encode($payload)], [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        ])->assertStatus(204);
    }

    public function test_lightweight_events_store_visitor_uuid(): void
    {
        $this->track('https://journey.test/', 'https://www.google.com/');

        $this->assertSame(1, LightweightEvent::count());

        $event = LightweightEvent::query()->first();
        $binary = $event->getRawOriginal('visitor_uuid');
        $hex = bin2hex(is_resource($binary) ? stream_get_contents($binary) : (string) $binary);

        $this->assertSame(str_replace('-', '', $this->visitorUuid), strtolower($hex));
    }

    public function test_visitors_list_aggregates_by_uuid(): void
    {
        $this->track('https://journey.test/', 'https://www.google.com/');
        $this->track('https://journey.test/pricing');
        $this->track('https://journey.test/docs');

        $html = $this->actingAs($this->user)
            ->get(route('stats.visitors', ['website' => $this->website->website_id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(substr(str_replace('-', '', $this->visitorUuid), 0, 8), $html);
    }

    public function test_visitor_detail_shows_journey_timeline(): void
    {
        $this->track('https://journey.test/landing', 'https://www.bing.com/');
        $this->track('https://journey.test/pricing');
        $this->track('https://journey.test/exit');

        $uuidHex = str_replace('-', '', $this->visitorUuid);

        $html = $this->actingAs($this->user)
            ->get(route('stats.visitor', ['website' => $this->website->website_id, 'visitorId' => $uuidHex]))
            ->assertOk()
            ->getContent();

        // 画像 + 时间线 + 进入/退出路径
        $this->assertStringContainsString('macOS', $html);
        $this->assertStringContainsString('/landing', $html);
        $this->assertStringContainsString('/exit', $html);
        $this->assertStringContainsString('www.bing.com', $html);
    }

    public function test_visitor_detail_rejects_unknown_uuid(): void
    {
        $this->actingAs($this->user)
            ->get(route('stats.visitor', ['website' => $this->website->website_id, 'visitorId' => str_repeat('a', 32)]))
            ->assertNotFound();
    }
}

