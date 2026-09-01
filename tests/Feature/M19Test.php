<?php

namespace Tests\Feature;

use App\Models\AccountLog;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M19 语义级校对缺口补齐验证（规格 §2 / §6.1 / §6.3.5）
 * - §2：已登录会话中 status != 1 的用户在下一请求被立即登出
 * - §6.3.5：/admin/logs/download 独立日志下载路由（CSV）
 * - §6.1：落地页货币切换器 + prices JSON 定价卡渲染
 */
class M19Test extends TestCase
{
    use RefreshDatabase;

    public function test_banned_user_session_is_terminated_on_next_request(): void
    {
        $user = User::create([
            'name' => '待封禁用户', 'email' => 'banned@test.dev',
            'password' => bcrypt('secret123'),
            'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ]);

        $this->post('/login', ['email' => 'banned@test.dev', 'password' => 'secret123']);
        $this->assertAuthenticatedAs($user);

        // 管理员封禁该用户（status != 1）
        $user->update(['status' => 0]);

        // 同一测试进程内 guard 缓存了封禁前的旧用户对象（真实环境每请求独立进程无此问题），
        // 重建 auth 实例以模拟"新请求从 session+DB 重新解析用户"
        $this->app->forgetInstance('auth');

        $response = $this->get('/dashboard');

        // §2：user 级权限守卫 —— 会话立即终止并跳回登录页
        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $response->assertSessionHas('error', __('msg.account_banned'));
    }

    public function test_active_user_is_not_affected_by_guard(): void
    {
        $user = User::create([
            'name' => '正常用户', 'email' => 'active@test.dev',
            'password' => bcrypt('secret123'),
            'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ]);

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_log_download_returns_csv(): void
    {
        $admin = User::create([
            'name' => '管理员', 'email' => 'admin19@test.dev',
            'password' => bcrypt('secret123'),
            'status' => 1, 'plan_id' => 'free', 'type' => 1,
        ]);

        AccountLog::create([
            'user_id' => $admin->user_id,
            'type' => 'm19.download_test',
            'ip' => '127.0.0.1',
            'datetime' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/logs/download');

        $response->assertStatus(200);
        $this->assertStringContainsString('monit-logs-', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('m19.download_test', $response->streamedContent());
    }

    public function test_non_admin_cannot_download_logs(): void
    {
        $user = User::create([
            'name' => '普通用户', 'email' => 'user19@test.dev',
            'password' => bcrypt('secret123'),
            'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ]);

        $this->actingAs($user)->get('/admin/logs/download')->assertStatus(403);
    }

    public function test_landing_currency_switcher(): void
    {
        Plan::create([
            'plan_id' => 'pro', 'name' => 'Pro',
            'prices' => ['CNY' => ['monthly' => 19.9], 'USD' => ['monthly' => 9.99]],
            'is_enabled' => true, 'order' => 1,
        ]);

        // 默认 CNY：渲染 prices JSON 月付价（CNY 符号本土化为「元」后置）
        $this->get('/')->assertOk()
            ->assertSee('19.90 元', false);

        // 切换到 USD 并持久化到 session
        $this->get('/?currency=USD')->assertOk()
            ->assertSee('<option value="USD" selected', false);

        // 后续无参请求仍保持 USD
        $this->get('/')->assertOk()
            ->assertSee('<option value="USD" selected', false);

        // 切回 CNY
        $this->get('/?currency=CNY')->assertOk()
            ->assertSee('<option value="CNY" selected', false);

        // 非法货币值被忽略（回落 session 当前值）
        $this->get('/?currency=XXX')->assertOk()
            ->assertSee('<option value="CNY" selected', false);
    }
}
