<?php

namespace Tests\Feature;

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin 敏感操作审计与 loginAs 硬化（审计会话：AdminUserUpdate）
 *
 * 审计缺口：
 * 1. loginAs 可 impersonate 其他管理员（横向接管：A 的会话静默获得 B 的全部
 *    权限且无痕）；可登录禁用/未确认用户（绕过 completeLogin 状态检查）
 * 2. update/toggleStatus/loginAs 均无 account_logs 留痕——封禁、改密、
 *    提权（type 0→1）事后不可追溯
 *
 * 修复：三操作全记审计日志（type 后缀 _by_{adminId} 编码操作者），
 * loginAs 拒绝 admin 目标与非激活用户，toggleStatus 拒绝自封禁。
 */
class AdminAuditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin-audit@example.test', 'password' => bcrypt('secret123'),
            'status' => 1, 'type' => 1, 'plan_id' => 'custom', 'plan_settings' => [],
        ]);
    }

    private function target(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Target', 'email' => 'target-audit@example.test', 'password' => bcrypt('secret123'),
            'status' => 1, 'type' => 0, 'plan_id' => 'free', 'plan_settings' => null,
        ], $overrides));
    }

    private function logsOf(User $user, string $action): array
    {
        return AccountLog::where('user_id', $user->user_id)
            ->where('type', 'like', $action.'%')
            ->pluck('type')
            ->all();
    }

    public function test_login_as_cannot_impersonate_another_admin(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->target(['type' => 1, 'email' => 'other-admin@example.test']);

        $this->actingAs($admin)
            ->post(route('admin.users.login-as', $otherAdmin->user_id))
            ->assertForbidden();

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertSame([], $this->logsOf($otherAdmin, 'admin_login_as'), '被拒操作不应留登录切换日志');
    }

    public function test_login_as_cannot_impersonate_disabled_user(): void
    {
        $admin = $this->admin();
        $disabled = $this->target(['status' => 2]);

        $this->actingAs($admin)
            ->post(route('admin.users.login-as', $disabled->user_id))
            ->assertForbidden();

        // 会话保持 admin，未被切换为禁用用户
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_login_as_active_user_succeeds_with_audit_log(): void
    {
        $admin = $this->admin();
        $user = $this->target();

        $this->actingAs($admin)
            ->post(route('admin.users.login-as', $user->user_id))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertContains(
            'admin_login_as_by_'.$admin->user_id,
            $this->logsOf($user, 'admin_login_as'),
            'impersonation 必须留痕（操作者 admin id 编码进 type）'
        );
    }

    public function test_toggle_status_cannot_ban_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.users.toggle_status', $admin->user_id))
            ->assertForbidden();

        $this->assertSame(1, $admin->fresh()->status, '自封禁被拒后状态不变');
    }

    public function test_toggle_status_writes_audit_log(): void
    {
        $admin = $this->admin();
        $user = $this->target();

        $this->actingAs($admin)
            ->put(route('admin.users.toggle_status', $user->user_id))
            ->assertRedirect();

        $this->assertSame(2, $user->fresh()->status);
        $this->assertContains(
            'admin_user_status_toggled_by_'.$admin->user_id,
            $this->logsOf($user, 'admin_user_status_toggled')
        );
    }

    public function test_update_writes_audit_log_and_promotion_flagged(): void
    {
        $admin = $this->admin();
        $user = $this->target();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user->user_id), [
                'name' => 'Target', 'email' => $user->email,
                'status' => 1, 'type' => 1, // 提权
            ])
            ->assertRedirect();

        $this->assertContains('admin_user_updated_by_'.$admin->user_id, $this->logsOf($user, 'admin_user_updated'));
        $this->assertContains(
            'admin_user_promoted_by_'.$admin->user_id,
            $this->logsOf($user, 'admin_user_promoted'),
            '提权（type 0→1）必须单独留痕'
        );
    }
}
