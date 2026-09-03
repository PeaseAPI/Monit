<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamMemberAssociation;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 安全审计周期 #12：
 * 1) /websites-ajax 跨租户搜索泄露（missing where-group）
 *    Website::where('user_id', X)->where('name', like)->orWhere('host', like)
 *    SQL 中 AND 优先于 OR → (user_id=X AND name LIKE) OR (host LIKE)
 *    —— 只要带 search 参数，其他用户 host 匹配的网站（全字段）即被返回。
 * 2) TeamMember 删除不级联清理 team_member_associations（孤儿数据，
 *    无泄露面但持续累积 —— deleting 钩子统一清理）。
 */
class WebsiteAjaxTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = User::create([
            'name' => 'Alice', 'email' => 'alice@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $this->bob = User::create([
            'name' => 'Bob', 'email' => 'bob@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $mkWebsite = fn (User $u, string $key, string $host) => Website::create([
            'user_id' => $u->user_id,
            'pixel_key' => 'px_'.$key, 'name' => 'Site '.$key,
            'scheme' => 'https', 'host' => $host,
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $mkWebsite($this->alice, 'alice_blog', 'alice-blog.test');     // Alice 的
        $mkWebsite($this->alice, 'alice_shop', 'alice-shop.test');      // Alice 的（host 含 shop）
        $mkWebsite($this->bob, 'bob_shop', 'bob-shop.test');            // Bob 的（host 含 shop）
    }

    #[Test]
    public function search_never_leaks_other_tenants_websites(): void
    {
        // Alice 搜索「shop」：自己有 alice-shop.test 匹配，Bob 的 bob-shop.test
        // 不得出现在结果中
        $response = $this->actingAs($this->alice)
            ->getJson('/websites-ajax?search=shop');

        $response->assertOk();
        $hosts = collect($response->json('data'))->pluck('host')->all();
        $this->assertSame(['alice-shop.test'], $hosts);
    }

    #[Test]
    public function search_matching_no_own_website_returns_empty(): void
    {
        // Alice 搜索「bob」：Alice 名下无匹配 → 空集（不返回 Bob 的网站）
        $response = $this->actingAs($this->alice)
            ->getJson('/websites-ajax?search=bob');

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    #[Test]
    public function search_by_name_still_works_for_own_websites(): void
    {
        $response = $this->actingAs($this->alice)
            ->getJson('/websites-ajax?search=blog');

        $response->assertOk();
        $hosts = collect($response->json('data'))->pluck('host')->all();
        $this->assertSame(['alice-blog.test'], $hosts);
    }

    #[Test]
    public function without_search_only_own_websites_are_listed(): void
    {
        $response = $this->actingAs($this->bob)
            ->getJson('/websites-ajax');

        $response->assertOk();
        $hosts = collect($response->json('data'))->pluck('host')->all();
        $this->assertSame(['bob-shop.test'], $hosts);
    }

    #[Test]
    public function deleting_member_cleans_up_associations(): void
    {
        $team = Team::create([
            'user_id' => $this->alice->user_id,
            'name' => 'Assoc Cleanup Team',
            'datetime' => now(),
        ]);
        $member = TeamMember::create([
            'team_id' => $team->team_id,
            'user_email' => $this->bob->email,
            'user_id' => $this->bob->user_id,
            'status' => 1,
            'datetime' => now(),
        ]);
        $website = Website::where('host', 'alice-blog.test')->firstOrFail();
        TeamMemberAssociation::create([
            'team_member_id' => $member->team_member_id,
            'website_id' => $website->website_id,
            'access' => ['read' => true],
            'datetime' => now(),
        ]);

        $member->delete();

        $this->assertSame(
            0,
            TeamMemberAssociation::where('team_member_id', $member->team_member_id)->count(),
            '删除成员后关联表必须清空（deleting 钩子）',
        );
    }
}
