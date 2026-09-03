<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 账号 email 变更安全测试（安全审计轮 #8）：
 *
 * update() 允许直接改绑邮箱且不要求密码确认——与其余敏感操作不一致
 * （改密码 / 删账户 / 关 2FA 均要求 current_password，绑手机要短信验证码）。
 * 会话窃取（cookie 泄露）场景下的接管链：
 *   改邮箱 → 忘记密码 → 重置邮件发往攻击者邮箱 → 完全接管
 * email_verified_at 重置并不能阻断该链。修复：email 变更时强制 current_password。
 */
class AccountEmailChangeSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'old@test.dev',
            'password' => bcrypt('secret123'),
            'type' => 1,
            'status' => 1,
            'plan_id' => 'free',
            'email_verified_at' => now(),
        ]);
    }

    public function test_email_change_without_password_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put('/account', [
                'name' => 'Admin',
                'email' => 'attacker@evil.dev',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('old@test.dev', $user->fresh()->email, '未提供密码时邮箱不得变更');
    }

    public function test_email_change_with_wrong_password_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put('/account', [
                'name' => 'Admin',
                'email' => 'attacker@evil.dev',
                'current_password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('old@test.dev', $user->fresh()->email, '密码错误时邮箱不得变更');
    }

    public function test_email_change_with_correct_password_succeeds_and_resets_verification(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put('/account', [
                'name' => 'Admin',
                'email' => 'new@test.dev',
                'current_password' => 'secret123',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $fresh = $user->fresh();
        $this->assertSame('new@test.dev', $fresh->email);
        $this->assertNull($fresh->email_verified_at, '邮箱变更后验证标记重置');
    }

    public function test_profile_update_keeping_same_email_needs_no_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put('/account', [
                'name' => '新昵称',
                'email' => 'old@test.dev',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->assertSame('新昵称', $user->fresh()->name, '原邮箱提交时无需密码（回归保护：不破坏日常资料编辑）');
    }
}
