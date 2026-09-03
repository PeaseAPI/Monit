<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 两步验证（TOTP）安全回归（规格 §12.4 + RFC 6238 §5.2）
 *
 * 审计缺口：原 TotpService::verify 无一次性消费——同一 6 位码在
 * 30~90 秒有效窗口内可无限重放。攻击链（钓鱼/中间人拦截「密码+码」
 * 或「登录码」后立刻重放）真实可达。
 *
 * 修复：TotpService::consume 记录 last-used counter（cache 120s），
 * 登录（verifyTwoFactor）与关闭 2FA（twofaDisable）共用判重池。
 */
class TwofaTest extends TestCase
{
    use RefreshDatabase;

    private function twofaUser(): User
    {
        return User::create([
            'name' => 'Twofa 用户',
            'email' => 'twofa@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => 'free',
            'twofa_token' => TotpService::generateSecret(),
            'twofa_is_enabled' => true,
        ]);
    }

    public function test_verify_counter_returns_matching_window(): void
    {
        $secret = TotpService::generateSecret();
        $counter = intdiv(time(), TotpService::PERIOD);
        $code = TotpService::code($secret, $counter);

        $this->assertSame($counter, TotpService::verifyCounter($secret, $code));
        $this->assertNull(TotpService::verifyCounter($secret, '000000'), '随机码不应命中（000000 概率忽略）');
        $this->assertNull(TotpService::verifyCounter($secret, 'abc'), '非数字码直接拒绝');
    }

    public function test_consume_allows_code_once_only(): void
    {
        $secret = TotpService::generateSecret();
        $code = TotpService::code($secret, intdiv(time(), TotpService::PERIOD));

        $this->assertTrue(TotpService::consume($secret, $code, 'test.pool'));
        $this->assertFalse(
            TotpService::consume($secret, $code, 'test.pool'),
            '同一码第二次消费必须被拒绝（RFC 6238 §5.2 一次性消费）'
        );
    }

    public function test_login_replays_same_code_is_rejected(): void
    {
        $user = $this->twofaUser();
        $code = TotpService::code($user->twofa_token, intdiv(time(), TotpService::PERIOD));

        // 第一次登录：密码 → 2FA 码 → 成功
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('login.twofa'));
        $this->post('/login/twofa', ['code' => $code])->assertRedirect();
        $this->assertAuthenticatedAs($user);

        // 登出后钓鱼场景重放同一码：必须拒绝
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('login.twofa'));
        $this->post('/login/twofa', ['code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_twofa_disable_replays_same_code_is_rejected(): void
    {
        $user = $this->twofaUser();
        $secret = (string) $user->getRawOriginal('twofa_token'); // disable 置空前留存
        $code = TotpService::code($secret, intdiv(time(), TotpService::PERIOD));

        // 正常关闭：密码 + 码 → 成功
        $this->actingAs($user)
            ->delete('/account/twofa', ['password' => 'secret123', 'code' => $code])
            ->assertRedirect();
        $this->assertFalse($user->fresh()->twofa_is_enabled);

        // 钓鱼重放场景：用户重新开启 2FA 后，攻击者立刻重放先前拦截的同一码
        // （不 flush cache——判重池必须跨请求生效）
        $user->forceFill(['twofa_token' => $secret, 'twofa_is_enabled' => true])->save();

        $this->actingAs($user)
            ->delete('/account/twofa', ['password' => 'secret123', 'code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertTrue(
            $user->fresh()->twofa_is_enabled,
            '重放的码不应能关闭 2FA'
        );
    }
}
