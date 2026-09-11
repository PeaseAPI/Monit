<?php

namespace Tests\Feature;

use App\Models\SessionEvent;
use App\Models\SessionReplay;
use App\Models\User;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use App\Support\Typed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * 全量巡检修复：Spotlight 聚焦搜索（spotlight.search）恒空。
 * 此前 SpotlightController 用 $user->id 查询（User 主键是 user_id）→ 恒 null，
 * 网站/会话搜索静默失效，永远返回空结果。
 */
class SpotlightSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Website}
     */
    private function seedUserAndWebsite(string $email, string $host): array
    {
        $user = User::create([
            'name' => 'Spot Owner', 'email' => $email,
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $website = Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_spot_'.md5($host), 'name' => 'Spotlight Site', 'scheme' => 'https',
            'host' => $host, 'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        return [$user, $website];
    }

    private function seedSession(Website $website, string $path): VisitorSession
    {
        $visitor = WebsiteVisitor::create([
            'website_id' => $website->website_id,
            'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
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
            'type' => 'pageview', 'path' => $path, 'title' => 'T',
            'date' => now(),
        ]);

        return $session;
    }

    public function test_spotlight_returns_owner_websites_by_host(): void
    {
        [$owner, $website] = $this->seedUserAndWebsite('spot-a@example.com', 'findme.test');

        $res = $this->actingAs($owner)
            ->getJson(route('spotlight.search', ['q' => 'findme']))
            ->assertOk()
            ->assertJsonStructure(['results']);

        $results = Typed::arr($res->json('results'));
        $types = collect($results)->pluck('type');
        $this->assertContains('website', $types);

        $hit = Typed::arr(collect($results)->firstWhere('type', 'website'));
        $this->assertSame($website->website_id, $hit['id']);
        $this->assertSame('findme.test', $hit['subtitle']);
    }

    public function test_spotlight_returns_sessions_by_event_path(): void
    {
        [$owner, $website] = $this->seedUserAndWebsite('spot-b@example.com', 'spotb.test');
        $session = $this->seedSession($website, '/unique-checkout-path');

        // 无回放记录 → 跳回放列表页（不能用 session_id 冒充 replay_id）
        $res = $this->actingAs($owner)
            ->getJson(route('spotlight.search', ['q' => 'unique-checkout']))
            ->assertOk();

        $results = Typed::arr($res->json('results'));
        $types = collect($results)->pluck('type');
        $this->assertContains('session', $types);

        $hit = Typed::arr(collect($results)->firstWhere('type', 'session'));
        $this->assertSame(
            route('stats.replays', $website),
            $hit['url'],
            '无回放记录时应跳回放列表页'
        );

        // 有回放记录 → 跳回放详情（参数必须是 replay_id）
        $replay = SessionReplay::create([
            'session_id' => $session->session_id,
            'visitor_id' => $session->visitor_id,
            'website_id' => $website->website_id,
            'user_id' => $owner->user_id,
            'events' => 1, 'size' => 2,
        ]);
        $res = $this->actingAs($owner)
            ->getJson(route('spotlight.search', ['q' => 'unique-checkout']))
            ->assertOk();

        $results = Typed::arr($res->json('results'));
        $hit = Typed::arr(collect($results)->firstWhere('type', 'session'));
        $this->assertSame(
            route('stats.replays.show', [$website, $replay->replay_id]),
            $hit['url'],
            '有回放记录时应使用 replay_id 跳详情页'
        );
    }

    public function test_spotlight_hides_other_users_data(): void
    {
        [, $website] = $this->seedUserAndWebsite('spot-owner@example.com', 'secret-site.test');
        $intruder = User::create([
            'name' => 'Intruder', 'email' => 'spot-x@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $res = $this->actingAs($intruder)
            ->getJson(route('spotlight.search', ['q' => 'secret-site']))
            ->assertOk();

        $types = collect(Typed::arr($res->json('results')))->pluck('type');
        $this->assertNotContains('website', $types);
    }

    public function test_spotlight_matches_nav_items_by_title(): void
    {
        $user = User::create([
            'name' => 'Nav User', 'email' => 'spot-nav@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $res = $this->actingAs($user)
            ->getJson(route('spotlight.search', ['q' => '仪表']))
            ->assertOk();

        $this->assertContains('nav', collect(Typed::arr($res->json('results')))->pluck('type'));
    }

    public function test_spotlight_requires_two_chars(): void
    {
        $user = User::create([
            'name' => 'Short User', 'email' => 'spot-short@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $this->actingAs($user)
            ->getJson(route('spotlight.search', ['q' => 'a']))
            ->assertOk()
            ->assertJson(['results' => []]);
    }
}
