<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 社交登录与 SSO 安全
 *
 * 审计缺口 1（Login CSRF）：callbackChinese() 对 qq/wechat/weibo/gitee/feishu
 * 五个国内 provider 完全不验证 OAuth state —— redirectChinese() 生成了 state 并存
 * session，但回调时零检查（且 provider 内用的是 csrf_token()，与 session 存值根本
 * 不一致）。攻击者可将自己的授权 code 以链接/iframe 发给受害者，callback 拿 code
 * 换 token 直接登录 —— 受害者浏览器被登录进**攻击者的账户**，之后填入的任何数据
 * （付款、网站配置、个人信息）攻击者事后可随时登录查看。海外 8 家 provider 均有
 * state 验证，唯独国内 5 家裸奔（OWASP OAuth Cheat Sheet: state 必须校验）。
 *
 * 审计缺口 2（HMAC 签名拼接歧义）：SsoController 的签名 payload 为
 * user_id.email.timestamp 直接拼接、无分隔符 —— (45, "5victim@x.com") 与
 * (455, "victim@x.com") 产生相同签名。攻击者可注册数字前缀邮箱获得针对自己
 * (user_id, email) 的合法签名，重放为受害者的 (user_id, email) 组合完成冒充。
 *
 * 审计缺口 3（Apple id_token 无 aud 校验）：getAppleUserInfo 只 base64 解码
 * payload 不验 aud —— 其他 Apple 应用的 id_token 可跨应用重放（纵深防御，
 * state 已挡主路径）。
 */
class SocialSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // 缺口 1：国内 provider 回调 state 验证
    // ---------------------------------------------------------------

    public function test_chinese_callback_rejects_forged_state(): void
    {
        // 攻击者诱导受害者访问：code 是攻击者自己的，state 是攻击者伪造的
        $this->get('/social-login/callback/qq?code=attacker-code&state=forged')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('oauth');

        $this->assertSame(
            __('auth.oauth_state_mismatch'),
            session('errors')->first('oauth'),
            '伪造 state 必须在触碰任何 token 端点之前被拒绝'
        );
        $this->assertGuest('web');
    }

    public function test_chinese_callback_rejects_missing_state(): void
    {
        $this->get('/social-login/callback/wechat?code=attacker-code')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('oauth');

        $this->assertGuest('web');
    }

    public function test_chinese_callback_happy_path_with_valid_state(): void
    {
        config([
            'services.qq.client_id' => 'qq-app-id',
            'services.qq.client_secret' => 'qq-app-secret',
        ]);

        // redirect 阶段生成 state 并存 session（模拟用户发起过登录）
        $this->withSession(['oauth_state_qq' => 'valid-state']);

        Http::fake([
            'graph.qq.com/oauth2.0/token' => Http::response('access_token=fake-token'),
            'graph.qq.com/oauth2.0/me*' => Http::response('callback( {"client_id":"qq-app-id","openid":"o12345"} );'),
            'graph.qq.com/user/get_user_info*' => Http::response(['nickname' => 'QQ用户', 'figureurl_qq_2' => 'https://q.qlogo.cn/a.png']),
        ]);

        $this->get('/social-login/callback/qq?code=legit-code&state=valid-state')
            ->assertRedirect(route('dashboard'));

        // QQ 无邮箱 → provider+id 虚拟邮箱注册并登录
        $user = User::where('email', 'qq_o12345@social.login')->first();
        $this->assertNotNull($user, '合法 state + code 应完成虚拟邮箱注册');
        $this->assertAuthenticatedAs($user, 'web');
    }

    // ---------------------------------------------------------------
    // 缺口 2：SSO 签名 payload 拼接歧义
    // ---------------------------------------------------------------

    private function enableSso(string $secret): void
    {
        Settings::set('main.sso_is_enabled', 'true');
        Settings::set('main.sso_secret_key', $secret);
    }

    public function test_sso_ambiguous_concat_cannot_impersonate(): void
    {
        $this->enableSso('sso-secret');

        User::create([
            'name' => 'Victim', 'email' => 'victim@x.com', 'password' => bcrypt('secret123'),
            'status' => 1, 'type' => 0, 'plan_id' => 'free', 'referral_key' => 'rk-victim',
        ]);
        User::create([
            'name' => 'Attacker', 'email' => '5victim@x.com', 'password' => bcrypt('secret123'),
            'status' => 1, 'type' => 0, 'plan_id' => 'free', 'referral_key' => 'rk-attacker',
        ]);

        // 攻击者合法获得针对自己 (user_id=45, email=5victim@x.com) 的签名
        // —— 但旧代码拼接 '45'.'5victim@x.com' 与受害者 (455, victim@x.com) 同串
        $ts = time();
        $legacyPayload = '45'.'5victim@x.com'.$ts;
        $signedToken = hash_hmac('sha256', $legacyPayload, 'sso-secret');

        $this->get('/sso?'.http_build_query([
            'token' => $signedToken,
            'timestamp' => $ts,
            'user_id' => 455,          // 受害者维度重解释
            'email' => 'victim@x.com',
        ]))->assertRedirect(route('login'));

        $this->assertGuest('web', '拼接歧义签名不得冒充受害者登录');
    }

    public function test_sso_delimited_signature_logs_in(): void
    {
        $this->enableSso('sso-secret');

        $user = User::create([
            'name' => 'SsoUser', 'email' => 'sso@x.com', 'password' => bcrypt('secret123'),
            'status' => 1, 'type' => 0, 'plan_id' => 'free', 'referral_key' => 'rk-sso',
        ]);

        $ts = time();
        // 新格式：冒号分隔，无拼接歧义
        $payload = implode(':', [(string) $user->user_id, 'sso@x.com', (string) $ts]);
        $token = hash_hmac('sha256', $payload, 'sso-secret');

        $this->get('/sso?'.http_build_query([
            'token' => $token,
            'timestamp' => $ts,
            'user_id' => $user->user_id,
            'email' => 'sso@x.com',
        ]))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
    }

    // ---------------------------------------------------------------
    // 缺口 3：Apple id_token aud 校验
    // ---------------------------------------------------------------

    public function test_apple_id_token_rejects_foreign_audience(): void
    {
        config(['services.apple.client_id' => 'com.monit.app']);

        $controller = new \App\Http\Controllers\SocialLoginController;
        $method = new \ReflectionMethod($controller, 'getAppleUserInfo');
        $method->setAccessible(true);

        $makeToken = function (array $payload): string {
            $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'ES256'])), '+/', '-_'), '=');
            $body = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
            return $header.'.'.$body.'.sig';
        };

        // 其他 Apple 应用签发的 id_token（aud 不匹配）必须拒绝
        $foreign = $method->invoke($controller, $makeToken([
            'sub' => 'apple-user-1', 'email' => 'attacker@x.com', 'aud' => 'com.evil.otherapp',
        ]));
        $this->assertNull($foreign, 'aud 非本应用 client_id 的 id_token 不得被接受');

        // aud 匹配的正常 token 正常解析
        $own = $method->invoke($controller, $makeToken([
            'sub' => 'apple-user-2', 'email' => 'user@x.com', 'aud' => 'com.monit.app',
        ]));
        $this->assertNotNull($own);
        $this->assertSame('user@x.com', $own['email']);
    }
}
