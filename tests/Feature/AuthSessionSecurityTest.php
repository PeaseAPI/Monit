<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TotpService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 周期 #18：认证/会话面
 *
 * 覆盖四类缺陷：
 * 1. 会话固定——所有登录路径 Auth::login 前后必须 regenerate session id
 * 2. OAuth email 信任链——GitHub/Discord 未验证 email 不得用于匹配本地账号
 * 3. 虚拟邮箱 pre-hijacking——@social.login 只能匹配同 provider 的社交账号
 * 4. 密码重置码 TTL + 激活邮件重发 per-email lockout
 */
class AuthSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'type' => 0,
            'name' => 'Test User',
            'email' => Str::random(8).'@test.dev',
            'password' => Hash::make('password123'),
            'plan_id' => 'free',
            'status' => 1,
            'source' => 'direct',
        ], $attrs));
    }

    /* ---------------- 会话固定 ---------------- */

    public function test_password_login_regenerates_session_id(): void
    {
        $user = $this->makeUser();

        session()->start(); // 未启动时 getId() 为 null，断言会假绿
        $before = session()->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId(), '登录成功后必须 regenerate session id（防会话固定）');
    }

    public function test_twofa_login_regenerates_session_id(): void
    {
        $secret = TotpService::generateSecret();
        $user = $this->makeUser([
            'twofa_is_enabled' => true,
            'twofa_token' => $secret,
        ]);

        session()->start(); // 未启动时 getId() 为 null，断言会假绿
        $before = session()->getId();

        $this->withSession([
            'twofa_user_id' => $user->user_id,
            'twofa_remember' => false,
            'twofa_expires_at' => now()->addMinutes(10)->timestamp,
        ])->post('/login/twofa', [
            'code' => TotpService::code($secret),
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId(), '2FA 通过后必须 regenerate session id');
    }

    public function test_register_regenerates_session_id(): void
    {
        session()->start(); // 未启动时 getId() 为 null，断言会假绿
        $before = session()->getId();

        $this->post('/register', [
            'name' => 'Reg User',
            'email' => 'reg@test.dev',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'reg@test.dev']);
        $this->assertNotSame($before, session()->getId(), '注册即登录后必须 regenerate session id');
    }

    public function test_sso_login_regenerates_session_id(): void
    {
        Settings::set('main.sso_is_enabled', 'true');
        Settings::set('main.sso_secret_key', 'test-sso-secret');

        $user = $this->makeUser(['email' => 'sso@test.dev']);

        $ts = time();
        $token = hash_hmac('sha256', ":sso@test.dev:{$ts}", 'test-sso-secret');

        session()->start(); // 未启动时 getId() 为 null，断言会假绿
        $before = session()->getId();

        $this->get('/sso?'.http_build_query([
            'token' => $token,
            'email' => 'sso@test.dev',
            'timestamp' => $ts,
        ]));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId(), 'SSO 登录后必须 regenerate session id');
    }

    /* ---------------- OAuth email 信任链 ---------------- */

    public function test_github_unverified_primary_email_cannot_take_over_account(): void
    {
        $victim = $this->makeUser(['email' => 'victim@test.dev']);

        Http::fake([
            '*github.com/login/oauth/access_token' => Http::response(['access_token' => 'tok']),
            '*api.github.com/user' => Http::response(['id' => 42, 'login' => 'attacker', 'email' => null]),
            // 攻击者在 GitHub 把受害者的邮箱设为 primary，但从未通过 GitHub 验证
            '*api.github.com/user/emails' => Http::response([
                ['email' => 'victim@test.dev', 'primary' => true, 'verified' => false],
            ]),
        ]);

        $this->withSession(['oauth_state_github' => 'state123'])
            ->get('/social-login/callback/github?code=abc&state=state123');

        $this->assertFalse(auth()->check(), '未验证的 GitHub email 不得用于匹配本地账号（账号接管）');
        $this->assertTrue(Hash::check('password123', $victim->fresh()->password));
    }

    public function test_github_picks_verified_email_over_unverified_primary(): void
    {
        Http::fake([
            '*github.com/login/oauth/access_token' => Http::response(['access_token' => 'tok']),
            '*api.github.com/user' => Http::response(['id' => 42, 'login' => 'attacker', 'email' => null]),
            '*api.github.com/user/emails' => Http::response([
                ['email' => 'unverified-claim@test.dev', 'primary' => true, 'verified' => false],
                ['email' => 'attacker-own@test.dev', 'primary' => false, 'verified' => true],
            ]),
        ]);

        $this->withSession(['oauth_state_github' => 'state123'])
            ->get('/social-login/callback/github?code=abc&state=state123');

        $this->assertAuthenticated();
        $this->assertSame('attacker-own@test.dev', auth()->user()->email, '必须选取 verified email，跳过未验证的 primary');
    }

    public function test_discord_unverified_email_rejected(): void
    {
        $victim = $this->makeUser(['email' => 'victim@test.dev']);

        Http::fake([
            '*discord.com/api/oauth2/token' => Http::response(['access_token' => 'tok']),
            '*discord.com/api/users/@me' => Http::response([
                'id' => '99',
                'username' => 'attacker',
                'global_name' => 'Attacker',
                'email' => 'victim@test.dev',
                'verified' => false,
            ]),
        ]);

        $this->withSession(['oauth_state_discord' => 'state123'])
            ->get('/social-login/callback/discord?code=abc&state=state123');

        $this->assertFalse(auth()->check(), 'Discord 未验证 email 不得用于登录匹配');
        $this->assertTrue(Hash::check('password123', $victim->fresh()->password));
    }

    /* ---------------- 虚拟邮箱 pre-hijacking ---------------- */

    private function fakeQq(string $openid): void
    {
        // 注意：pattern 匹配的是含 query string 的完整 URL（Str::is），
        // 带查询参数的 GET 必须加尾部 *，否则不匹配 → 请求会漏到真实网络
        Http::fake([
            '*qq.com/oauth2.0/token*' => Http::response('access_token=tok&expires_in=7776000'),
            '*qq.com/oauth2.0/me*' => Http::response("callback( {\"client_id\":\"10001\",\"openid\":\"{$openid}\"} );"),
            '*qq.com/user/get_user_info*' => Http::response([
                'nickname' => 'QQ用户',
                'figureurl_qq_1' => 'http://q.qlogo.cn/a.png',
            ]),
        ]);
    }

    private function qqCallback(string $openid)
    {
        // QQ 提供商配置（getChineseProviderConfig 现要求非空 id/secret）
        config([
            'services.qq.client_id' => 'test-app-id',
            'services.qq.client_secret' => 'test-app-secret',
        ]);

        // 先走 redirect 拿到写入 session 的 state
        $this->get('/social-login/qq');
        $state = session('oauth_state_qq');

        return $this->get("/social-login/callback/qq?code=abc&state={$state}");
    }

    public function test_social_login_virtual_email_cannot_match_pre_registered_local_account(): void
    {
        // 攻击者抢先本地注册虚拟邮箱（格式合法，能通过 email 校验）
        $attacker = $this->makeUser([
            'email' => 'qq_8888@social.login',
            'source' => 'direct',
        ]);

        $this->fakeQq('8888');

        // 真实 QQ 用户（openid 8888）登录——不得落入攻击者预注册的账号
        $this->qqCallback('8888');

        $this->assertFalse(auth()->check(), '@social.login 虚拟邮箱不得匹配本地预注册账号（pre-hijacking）');
        $this->assertTrue(Hash::check('password123', $attacker->fresh()->password));
    }

    public function test_social_login_virtual_email_matches_same_provider_account(): void
    {
        // 正常回归：同 provider 社交账号的虚拟邮箱匹配不受影响
        $qqUser = $this->makeUser([
            'email' => 'qq_8888@social.login',
            'source' => 'qq',
        ]);

        $this->fakeQq('8888');

        $this->qqCallback('8888');

        $this->assertAuthenticatedAs($qqUser);
    }

    public function test_social_login_regenerates_session_id(): void
    {
        session()->start(); // 未启动时 getId() 为 null，断言会假绿
        $before = session()->getId();

        $this->fakeQq('9999');
        $this->qqCallback('9999');

        $this->assertAuthenticated();
        $this->assertNotSame($before, session()->getId(), '社交登录后必须 regenerate session id');
    }

    /* ---------------- 重置码 TTL + 激活重发 lockout ---------------- */

    public function test_expired_reset_code_rejected(): void
    {
        $user = $this->makeUser(['email' => 'reset@test.dev']);

        $user->forceFill([
            'lost_password_code' => Str::random(64),
            'lost_password_sent_at' => now()->subHours(2),
        ])->save();

        $this->post('/reset-password', [
            'code' => $user->lost_password_code,
            'email' => $user->email,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ]);

        $this->assertFalse(
            Hash::check('newpassword1', $user->fresh()->password),
            '过期的重置码不得修改密码'
        );
    }

    public function test_fresh_reset_code_still_works(): void
    {
        $user = $this->makeUser(['email' => 'reset2@test.dev']);
        $code = Str::random(64);

        $user->forceFill([
            'lost_password_code' => $code,
            'lost_password_sent_at' => now(),
        ])->save();

        $this->post('/reset-password', [
            'code' => $code,
            'email' => $user->email,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ]);

        $this->assertTrue(Hash::check('newpassword1', $user->fresh()->password));
    }

    public function test_activation_resend_per_email_lockout(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        // 任意 email（含未注册的）连续请求 3 次后锁定——与找回密码的 per-email 防轰炸同标准
        for ($i = 0; $i < 3; $i++) {
            $this->post('/resend-activation', ['email' => 'bomb@test.dev'])
                ->assertRedirect(route('activation.sent'));
        }

        $this->post('/resend-activation', ['email' => 'bomb@test.dev'])
            ->assertSessionHasErrors('email');
    }
}
