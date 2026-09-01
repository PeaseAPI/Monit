<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\InternalNotification;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 登录后导航补齐（对标 monit.cn）：
 * - 侧边栏 Domains / Teams / Notifications 入口与未读徽标
 * - Domains 完整闭环（表单字段对齐 + SEO 监控开关 + 越权防护）
 * - 通知中心（渲染 data JSON + 全部已读 + 越权防护）
 * - 仪表盘视图入口与创建（JSON 文本域 settings 归一化）
 * - 团队越权防护（show/invite/remove/destroy 仅限 owner/成员）
 */
class UserNavFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email = 'owner@test.dev'): User
    {
        return User::create([
            'name' => 'Owner', 'email' => $email,
            'password' => bcrypt('secret123'), 'type' => 1,
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => -1, 'domains_limit' => -1, 'dashboard_views_limit' => -1],
        ]);
    }

    private function stranger(): User
    {
        return $this->user('stranger@test.dev');
    }

    /* -------------------- 侧边栏 -------------------- */

    public function test_sidebar_contains_domains_teams_notifications_entries(): void
    {
        $user = $this->user();

        $response = $this->actingAs($user)->get('/websites');

        $response->assertOk()
            ->assertSee(route('domains.index'), false)
            ->assertSee(route('teams.index'), false)
            ->assertSee(route('notifications.index'), false);
    }

    public function test_sidebar_shows_unread_notifications_badge(): void
    {
        $user = $this->user();

        foreach ([1, 2] as $i) {
            InternalNotification::create([
                'user_id' => $user->user_id,
                'for_type' => 'admin',
                'data' => ['title' => "Notice {$i}"],
                'is_read' => false,
                'datetime' => now(),
            ]);
        }

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('>2</span>', false);
    }

    /* -------------------- Domains -------------------- */

    public function test_domains_roundtrip_from_create_to_delete(): void
    {
        $user = $this->user();

        // 创建表单使用 host 字段（与控制器校验对齐）
        $this->actingAs($user)->get('/domains/create')
            ->assertOk()
            ->assertSee('name="host"', false);

        $this->actingAs($user)->post('/domains', ['host' => 'WWW.Example.com'])
            ->assertRedirect(route('domains.index'));

        $domain = Domain::where('user_id', $user->user_id)->first();
        $this->assertNotNull($domain);
        $this->assertSame('www.example.com', $domain->host);

        // 列表页展示 host 与添加入口
        $this->actingAs($user)->get('/domains')
            ->assertOk()
            ->assertSee('www.example.com')
            ->assertSee(route('domains.create'), false);

        // 开启 SEO 监控
        $this->actingAs($user)->put('/domains', [
            'domain_id' => $domain->domain_id,
            'monitor_is_enabled' => 1,
        ])->assertRedirect(route('domains.index'));

        $this->assertTrue((bool) $domain->refresh()->monitor_is_enabled);

        // 删除
        $this->actingAs($user)->delete("/domains/{$domain->domain_id}")
            ->assertRedirect(route('domains.index'));

        $this->assertDatabaseMissing('domains', ['domain_id' => $domain->domain_id]);
    }

    public function test_domains_cannot_be_updated_or_deleted_by_others(): void
    {
        $owner = $this->user();
        $stranger = $this->stranger();

        $domain = Domain::create([
            'user_id' => $owner->user_id,
            'host' => 'owner-only.test',
            'scheme' => 'https',
            'is_enabled' => true,
            'datetime' => now(),
        ]);

        $this->actingAs($stranger)->put('/domains', [
            'domain_id' => $domain->domain_id,
            'monitor_is_enabled' => 1,
        ])->assertNotFound();

        $this->actingAs($stranger)->delete("/domains/{$domain->domain_id}")->assertNotFound();

        $this->assertDatabaseHas('domains', ['domain_id' => $domain->domain_id, 'monitor_is_enabled' => false]);
    }

    /* -------------------- 通知中心 -------------------- */

    public function test_notifications_center_flow(): void
    {
        $user = $this->user();

        foreach (['Alpha alert', 'Beta alert'] as $title) {
            InternalNotification::create([
                'user_id' => $user->user_id,
                'for_type' => 'admin',
                'data' => ['title' => $title, 'description' => 'desc'],
                'is_read' => false,
                'datetime' => now(),
            ]);
        }

        // 列表渲染 data JSON 标题 + 全部已读按钮
        $this->actingAs($user)->get('/notifications')
            ->assertOk()
            ->assertSee('Alpha alert')
            ->assertSee(__('notifications.read_all'));

        // 全部标记已读
        $this->actingAs($user)->put('/notifications/read-all')->assertRedirect();
        $this->assertSame(0, InternalNotification::where('user_id', $user->user_id)->where('is_read', false)->count());

        // 单条删除
        $first = InternalNotification::where('user_id', $user->user_id)->first();
        $this->actingAs($user)->delete("/notifications/{$first->internal_notification_id}")->assertRedirect();
        $this->assertDatabaseMissing('internal_notifications', ['internal_notification_id' => $first->internal_notification_id]);
    }

    public function test_notifications_cannot_be_read_or_deleted_by_others(): void
    {
        $owner = $this->user();
        $stranger = $this->stranger();

        $notification = InternalNotification::create([
            'user_id' => $owner->user_id,
            'for_type' => 'admin',
            'data' => ['title' => 'Secret'],
            'is_read' => false,
            'datetime' => now(),
        ]);

        $this->actingAs($stranger)->put("/notifications/{$notification->internal_notification_id}/read")->assertNotFound();
        $this->actingAs($stranger)->delete("/notifications/{$notification->internal_notification_id}")->assertNotFound();

        $this->assertDatabaseHas('internal_notifications', [
            'internal_notification_id' => $notification->internal_notification_id,
            'is_read' => false,
        ]);
    }

    /* -------------------- 仪表盘视图 -------------------- */

    public function test_dashboard_links_to_views_management_and_store_normalizes_json_settings(): void
    {
        $user = $this->user();

        \App\Models\Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_nav_1', 'name' => 'Nav Site',
            'scheme' => 'https', 'host' => 'nav.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee(route('dashboard-views.index'), false);

        // 表单以 JSON 文本域提交 settings，控制器应归一化为数组存储
        $this->actingAs($user)->post('/dashboard-views', [
            'name' => 'Focus view',
            'settings' => '{"widgets":["visitors","pageviews"]}',
            'order' => 1,
        ])->assertRedirect();

        $view = \App\Models\DashboardView::where('user_id', $user->user_id)->first();
        $this->assertNotNull($view);
        $this->assertSame(['widgets' => ['visitors', 'pageviews']], $view->settings);
    }

    /* -------------------- 团队 -------------------- */

    public function test_teams_create_and_ownership_protection(): void
    {
        $owner = $this->user();
        $stranger = $this->stranger();

        $this->actingAs($owner)->post('/teams', ['name' => 'Growth team'])
            ->assertRedirect(route('teams.index'));

        $team = Team::where('user_id', $owner->user_id)->first();
        $this->assertNotNull($team);
        $this->assertSame('Growth team', $team->name);

        // owner 可访问团队详情
        $this->actingAs($owner)->get("/teams/{$team->team_id}")->assertOk();

        // 陌生人无法查看 / 解散他人团队
        $this->actingAs($stranger)->get("/teams/{$team->team_id}")->assertNotFound();
        $this->actingAs($stranger)->delete("/teams/{$team->team_id}")->assertNotFound();

        $this->assertDatabaseHas('teams', ['team_id' => $team->team_id]);
    }
}
