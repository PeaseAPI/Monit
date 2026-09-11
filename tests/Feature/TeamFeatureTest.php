<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamMemberAssociation;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A7：团队协作功能闭环测试
 * 修复内容：创建表单缺失、邀请时网站授权参数被丢弃、详情页无管理 UI、成员名显示
 */
class TeamFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => '团队用户', 'email' => uniqid('tm-').'@team.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ], $attrs));
    }

    protected function makeWebsite(User $user, string $host = 'example.com'): Website
    {
        return Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_'.uniqid(),
            'name' => 'Site '.$host, 'scheme' => 'https', 'host' => $host,
            'tracking_type' => 'advanced', 'is_enabled' => true, 'datetime' => now(),
        ]);
    }

    public function test_owner_can_create_team_and_page_shows_creation_form(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner)->post('/teams', ['name' => '增长团队'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(Team::where('name', '增长团队')->where('user_id', $owner->user_id)->exists());

        $this->actingAs($owner)->get('/teams')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('增长团队');
    }

    public function test_invite_grants_website_associations_and_member_can_accept(): void
    {
        $owner = $this->makeUser();
        $invitee = $this->makeUser(['email' => 'invitee@team.test']);
        $website = $this->makeWebsite($owner);
        $team = Team::create(['user_id' => $owner->user_id, 'name' => 'T1', 'datetime' => now()]);

        // 邀请并授权网站（此前 websites_ids 被直接丢弃）
        $this->actingAs($owner)->post('/teams/invite', [
            'team_id' => $team->team_id,
            'user_email' => 'invitee@team.test',
            'websites_ids' => [$website->website_id],
        ])->assertSessionHas('success');

        $member = TeamMember::where('team_id', $team->team_id)->where('user_email', 'invitee@team.test')->firstOrFail();
        $this->assertNotNull($member);
        $this->assertSame([$website->website_id], $member->websites_ids);
        $this->assertSame(1, TeamMemberAssociation::where('team_member_id', $member->team_member_id)->count());

        // 重复邀请被拒
        $this->actingAs($owner)->post('/teams/invite', [
            'team_id' => $team->team_id, 'user_email' => 'invitee@team.test',
        ])->assertSessionHasErrors('user_email');

        // 被邀请人接受
        $this->actingAs($invitee)->put('/teams/accept/'.$member->team_member_id)
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, $this->freshModel($member)->status);
        $this->assertSame($invitee->user_id, $this->freshModel($member)->user_id);

        // 成员可访问团队详情（含授权网站显示）
        $this->actingAs($invitee)->get('/teams/'.$team->team_id)
            ->assertOk()
            ->assertSee('invitee@team.test');

        // 接受他人名下的邀请 → 403（member.email 不匹配）
        $stranger = $this->makeUser(['email' => 'stranger@team.test']);
        $this->actingAs($stranger)->put('/teams/accept/'.$member->team_member_id)->assertStatus(403);
    }

    public function test_owner_can_remove_member_and_delete_team(): void
    {
        $owner = $this->makeUser();
        $memberUser = $this->makeUser(['email' => 'removable@team.test']);
        $website = $this->makeWebsite($owner);
        $team = Team::create(['user_id' => $owner->user_id, 'name' => 'T2', 'datetime' => now()]);

        $member = TeamMember::create([
            'team_id' => $team->team_id, 'user_email' => 'removable@team.test',
            'user_id' => $memberUser->user_id, 'status' => 1, 'datetime' => now(),
        ]);
        TeamMemberAssociation::create([
            'team_member_id' => $member->team_member_id, 'website_id' => $website->website_id,
            'access' => ['view'], 'datetime' => now(),
        ]);

        // 非 owner 移除 → 403
        $this->actingAs($memberUser)->delete('/teams/members/'.$member->team_member_id)->assertStatus(403);

        // owner 移除成员 → 关联级联清理
        $this->actingAs($owner)->delete('/teams/members/'.$member->team_member_id)
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('team_members', ['team_member_id' => $member->team_member_id]);
        $this->assertDatabaseMissing('team_member_associations', ['team_member_id' => $member->team_member_id]);

        // 解散团队
        $this->actingAs($owner)->delete('/teams/'.$team->team_id)->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('teams', ['team_id' => $team->team_id]);
    }

    public function test_invite_rejects_websites_owned_by_others(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser(['email' => 'other-owner@team.test']);
        $foreignWebsite = $this->makeWebsite($other, 'other-site.com');
        $team = Team::create(['user_id' => $owner->user_id, 'name' => 'T3', 'datetime' => now()]);

        $this->actingAs($owner)->post('/teams/invite', [
            'team_id' => $team->team_id,
            'user_email' => 'x@team.test',
            'websites_ids' => [$foreignWebsite->website_id],
        ])->assertSessionHas('success');

        // 越权网站被过滤，不产生授权关联
        $this->assertSame(0, TeamMemberAssociation::count());
    }
}
