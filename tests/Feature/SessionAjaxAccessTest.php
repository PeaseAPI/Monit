<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamMemberAssociation;
use App\Models\User;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * 安全审计周期 #15：/session-ajax 会话详情 AJAX 访问控制
 *
 * 修复前的双重缺陷（fail-closed，端点对所有人 500）：
 * - $request->user()->id 恒为 null（User 主键是 user_id）→ 所有权比对永假
 * - Website::teamMembers() 关系不存在 → BadMethodCallException 500
 *
 * 修复：user_id 比对 + TeamMemberAssociation（成员↔网站关联网）校验
 */
class SessionAjaxAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Website $website;

    private VisitorSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner', 'email' => 'owner@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $this->website = Website::create([
            'user_id' => $this->owner->user_id,
            'pixel_key' => 'px_s15', 'name' => 'S15 Site', 'scheme' => 'https',
            'host' => 's15.test', 'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);
        $visitor = WebsiteVisitor::create([
            'website_id' => $this->website->website_id,
            'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
            'country_code' => 'CN', 'device_type' => 'desktop',
            'os_name' => 'macOS', 'browser_name' => 'Chrome',
            'date' => now(), 'last_date' => now(),
        ]);
        $this->session = VisitorSession::create([
            'website_id' => $this->website->website_id,
            'visitor_id' => $visitor->visitor_id,
            'session_uuid_binary' => Uuid::uuid4()->getBytes(),
            'date' => now(), 'total_events' => 0,
        ]);
    }

    public function test_owner_can_access_own_session(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/session-ajax/{$this->session->session_id}")
            ->assertStatus(200);
    }

    public function test_other_user_is_forbidden(): void
    {
        $other = User::create([
            'name' => 'Other', 'email' => 'other@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $this->actingAs($other)
            ->getJson("/session-ajax/{$this->session->session_id}")
            ->assertStatus(403);
    }

    public function test_team_member_with_association_can_access(): void
    {
        $member = User::create([
            'name' => 'Member', 'email' => 'member@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $team = Team::create([
            'user_id' => $this->owner->user_id, 'name' => 'S15 Team', 'datetime' => now(),
        ]);
        $teamMember = TeamMember::create([
            'team_id' => $team->team_id, 'user_id' => $member->user_id,
            'user_email' => $member->email, 'datetime' => now(),
        ]);
        TeamMemberAssociation::create([
            'team_member_id' => $teamMember->team_member_id,
            'website_id' => $this->website->website_id,
            'datetime' => now(),
        ]);

        $this->actingAs($member)
            ->getJson("/session-ajax/{$this->session->session_id}")
            ->assertStatus(200);
    }

    public function test_team_member_without_association_is_forbidden(): void
    {
        $member = User::create([
            'name' => 'Member2', 'email' => 'member2@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $team = Team::create([
            'user_id' => $this->owner->user_id, 'name' => 'S15 Team2', 'datetime' => now(),
        ]);
        TeamMember::create([
            'team_id' => $team->team_id, 'user_id' => $member->user_id,
            'user_email' => $member->email, 'datetime' => now(),
        ]);
        // 无 TeamMemberAssociation：成员未被授权访问该网站

        $this->actingAs($member)
            ->getJson("/session-ajax/{$this->session->session_id}")
            ->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->getJson("/session-ajax/{$this->session->session_id}")
            ->assertStatus(401);
    }
}
