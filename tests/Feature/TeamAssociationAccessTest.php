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
 * 安全审计周期 #11：/teams-associations-ajax 越权读取（IDOR）
 *
 * 缺陷：associationsAjax 仅做 is_numeric(member_id) 检查，无任何归属校验
 * —— 任意已登录用户可枚举自增 member_id，读取全平台任意团队成员的
 * 网站关联（with('website') 带出 Website 全字段：host、settings、excluded_ips 等）。
 *
 * 修复语义：仅「该成员本人」或「该成员所属团队的 owner」可读取；
 * 其余（含不存在 member）一律返回空集合（不泄露资源存在性）。
 */
class TeamAssociationAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $member;

    private User $stranger;

    private TeamMember $teamMember;

    protected function setUp(): void
    {
        parent::setUp();

        $mkUser = fn (string $name) => User::create([
            'name' => $name,
            'email' => strtolower($name).'@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => 'free',
        ]);

        $this->owner = $mkUser('Alice');
        $this->member = $mkUser('Bob');
        $this->stranger = $mkUser('Carol');

        $team = Team::create([
            'user_id' => $this->owner->user_id,
            'name' => 'Audit Team',
            'datetime' => now(),
        ]);

        $this->teamMember = TeamMember::create([
            'team_id' => $team->team_id,
            'user_email' => $this->member->email,
            'user_id' => $this->member->user_id,
            'status' => 1, // accepted
            'datetime' => now(),
        ]);

        $website = Website::create([
            'user_id' => $this->owner->user_id,
            'pixel_key' => 'px_team', 'name' => 'Team Site',
            'scheme' => 'https', 'host' => 'team-site.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        TeamMemberAssociation::create([
            'team_member_id' => $this->teamMember->team_member_id,
            'website_id' => $website->website_id,
            'access' => ['read' => true],
            'datetime' => now(),
        ]);
    }

    #[Test]
    public function member_self_can_read_own_associations(): void
    {
        $response = $this->actingAs($this->member)
            ->getJson('/teams-associations-ajax?member_id='.$this->teamMember->team_member_id);

        $response->assertOk();
        $this->assertSame('team-site.test', $response->json('0.website.host'));
    }

    #[Test]
    public function team_owner_can_read_member_associations(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/teams-associations-ajax?member_id='.$this->teamMember->team_member_id);

        $response->assertOk();
        $this->assertSame('team-site.test', $response->json('0.website.host'));
    }

    #[Test]
    public function unrelated_user_gets_empty_result(): void
    {
        $response = $this->actingAs($this->stranger)
            ->getJson('/teams-associations-ajax?member_id='.$this->teamMember->team_member_id);

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    #[Test]
    public function nonexistent_member_returns_empty(): void
    {
        $response = $this->actingAs($this->stranger)
            ->getJson('/teams-associations-ajax?member_id=99999');

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    #[Test]
    public function missing_or_invalid_member_id_returns_empty(): void
    {
        foreach (['', 'member_id=abc'] as $query) {
            $response = $this->actingAs($this->stranger)
                ->getJson('/teams-associations-ajax'.($query === '' ? '' : '?'.$query));

            $response->assertOk();
            $this->assertSame([], $response->json());
        }
    }
}
