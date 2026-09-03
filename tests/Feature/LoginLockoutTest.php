<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LoginLockout;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * 登录/找回密码失败锁定（users.login_lockout_* / users.lost_password_lockout_*）
 *
 * 审计缺口：两组设置在后台可配且默认启用，但登录与找回密码流程完全未消费
 * ——仅有路由级 throttle:10,1（IP 维度 10 次/分钟），无按账户的失败锁定，
 * 管理员以为开启的爆破防护实际不存在（10 次/分钟 ≈ 1.4 万次/天可持续爆破）。
 *
 * 修复：LoginLockout 服务按邮箱/手机号维度 N 次失败锁 M 分钟（默认 5/30），
 * 锁定期间正确凭证也被拒绝；成功登录清零。
 */
class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 失败锁定测试需要连续多次请求，绕开路由级 throttle:10,1,login（IP 维度）
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function tearDown(): void
    {
        // Settings 静态快照跨测试残留（DB 事务回滚但 static::$cached 不回滚）
        \App\Support\Settings::flush();
        parent::tearDown();
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Lockout', 'email' => 'lockout@example.test', 'password' => bcrypt('secret123'),
            'status' => 1, 'type' => 0, 'plan_id' => 'free',
        ]);
    }

    private function failLogin(int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->post('/login', ['email' => 'lockout@example.test', 'password' => 'wrong-pass']);
        }
    }

    public function test_default_settings_lock_after_5_failures(): void
    {
        $user = $this->user();

        $this->failLogin(5);

        // 第 6 次：即使密码正确也必须被拒
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_locked_message_and_correct_password_blocked(): void
    {
        $user = $this->user();
        $this->failLogin(5);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'secret123']);

        $response->assertSessionHasErrors('email');
        $this->assertSame(
            __('auth.login_locked'),
            session('errors')->first('email'),
            '锁定期间应返回明确的锁定提示'
        );
    }

    public function test_successful_login_clears_failure_count(): void
    {
        $user = $this->user();

        // 4 次失败 → 1 次成功（清零）→ 再 4 次失败 → 仍不锁定
        $this->failLogin(4);
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
        $this->post('/logout');

        $this->failLogin(4);
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_lockout_disabled_via_settings(): void
    {
        Settings::set('users.login_lockout_is_enabled', 'false');
        $user = $this->user();

        $this->failLogin(10);

        // 开关关闭：不锁定，正确密码可登录
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_lost_password_locks_after_3_requests(): void
    {
        // 找回密码锁定（默认 3 次/30 分钟，防邮件轰炸）
        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => 'anyone@example.test']);
        }

        $this->post('/forgot-password', ['email' => 'anyone@example.test'])
            ->assertSessionHasErrors('email');

        // 其他邮箱不受影响
        $this->post('/forgot-password', ['email' => 'other@example.test'])
            ->assertSessionHasNoErrors();
    }

    public function test_unit_semantics_count_lock_and_clear(): void
    {
        // 服务级语义：计数 → 触发锁定 → 清零恢复
        $id = 'unit@example.test';
        $this->assertFalse(LoginLockout::blocked('login', $id));

        for ($i = 0; $i < 5; $i++) {
            LoginLockout::recordFailure('login', $id);
        }

        $this->assertTrue(LoginLockout::blocked('login', $id));

        LoginLockout::clear('login', $id);
        $this->assertFalse(LoginLockout::blocked('login', $id));
    }
}
