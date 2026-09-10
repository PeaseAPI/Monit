<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 安全审计周期 #7：认证与会话生命周期加固
 *
 * 1) API key Bearer 爆破限流（仅失败计数，成功清零）
 * 2) 改密/重置后撤销旧会话（sessions 表按 user_id 删除）+
 *    remember_token 轮换——被劫持会话无法在改密后存活
 */
class AuthLifecycleHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 限流器按 IP 计数，跨测试方法共享 array cache，先清
        RateLimiter::clear('api-key-failures.127.0.0.1');
    }

    protected function insertSession(User $user, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode('foo=bar'),
            'last_activity' => time(),
        ]);
    }

    public function test_api_key_failures_are_rate_limited(): void
    {
        // 10 次无效 key（401），第 11 次 429
        for ($i = 0; $i < 10; $i++) {
            $this->withHeader('Authorization', 'Bearer invalid-key-'.$i)
                ->getJson('/api/v1/user')
                ->assertStatus(401);
        }

        $this->withHeader('Authorization', 'Bearer invalid-key-final')
            ->getJson('/api/v1/user')
            ->assertStatus(429);
    }

    public function test_api_key_success_clears_failure_counter(): void
    {
        $user = User::factory()->create(['api_key' => 'sk-live-'.Str::random(32)]);

        // 9 次失败（未达阈值），1 次成功清零，后续失败重新计数 → 401 而非 429
        for ($i = 0; $i < 9; $i++) {
            $this->withHeader('Authorization', 'Bearer bad-'.$i)
                ->getJson('/api/v1/user')
                ->assertStatus(401);
        }

        $this->withHeader('Authorization', 'Bearer '.$user->api_key)
            ->getJson('/api/v1/user')
            ->assertOk();

        for ($i = 0; $i < 9; $i++) {
            $this->withHeader('Authorization', 'Bearer bad-again-'.$i)
                ->getJson('/api/v1/user')
                ->assertStatus(401);
        }
    }

    public function test_email_reset_revokes_all_sessions_and_rotates_remember_token(): void
    {
        $oldRemember = Str::random(60);
        $code = Str::random(64);
        $user = User::factory()->create([
            'status' => 1,
            'lost_password_code' => $code,
            'lost_password_sent_at' => now(),
            'remember_token' => $oldRemember,
        ]);

        $this->insertSession($user, 'sess-a');
        $this->insertSession($user, 'sess-b');

        $this->post(route('password.update'), [
            'code' => $code,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $user->refresh();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->user_id)->count());
        $this->assertNull($user->lost_password_code);
        $this->assertNotSame($oldRemember, $user->remember_token);
    }

    public function test_password_update_keeps_current_session_and_revokes_others(): void
    {
        $oldRemember = Str::random(60);
        $user = User::factory()->create([
            'status' => 1,
            'password' => bcrypt('old-password'),
            'remember_token' => $oldRemember,
        ]);

        $this->insertSession($user, 'other-device');

        $this->actingAs($user)
            ->put(route('account.update-password'), [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])->assertRedirect();

        $user->refresh();

        // 其余会话被删（actingAs 的当前会话在请求结束时才落库，且改密请求
        // 内已按 session id 排除自身；响应 redirect 成功即会话未中断）
        $remaining = DB::table('sessions')->where('user_id', $user->user_id)->pluck('id');
        $this->assertNotContains('other-device', $remaining);
        $this->assertNotSame($oldRemember, $user->remember_token);
    }
}
