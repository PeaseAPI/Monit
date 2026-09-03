<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Sms\SmsService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * M17 §12.5 短信验证：注册 / 手机号登录 / 找回密码 / 绑定手机号
 * 使用 log provider（验证码写日志 + Cache，不实际发短信）
 */
class SmsAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 开启短信（log provider）+ 四个场景
        Settings::set('sms.sms_is_enabled', true);
        Settings::set('sms.sms_provider', 'log');
        Settings::set('sms.sms_register_is_enabled', true);
        Settings::set('sms.sms_phone_login_is_enabled', true);
        Settings::set('sms.sms_forgot_password_is_enabled', true);
        Settings::set('sms.sms_phone_bind_is_enabled', true);
    }

    /** 从 Cache 取出验证码（log driver 下生产端写入处） */
    protected function codeFromCache(string $phone, string $purpose): string
    {
        return (string) Cache::get("monit.sms.{$purpose}.".SmsService::normalizePhone($phone));
    }

    public function test_register_with_phone_and_sms_code(): void
    {
        [$ok] = SmsService::send('13800138000', 'register');
        $this->assertTrue($ok);

        $code = $this->codeFromCache('13800138000', 'register');
        $this->assertEquals(6, strlen($code));

        $response = $this->post('/register', [
            'name' => 'Sms 用户',
            'email' => 'smsuser@test.dev',
            'password' => 'password123',
            'phone' => '13800138000',
            'sms_code' => $code,
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'smsuser@test.dev')->first();
        $this->assertNotNull($user);
        $this->assertSame('13800138000', $user->phone);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_register_rejects_wrong_sms_code(): void
    {
        SmsService::send('13800138001', 'register');

        $response = $this->post('/register', [
            'name' => 'Sms 用户2',
            'email' => 'smsuser2@test.dev',
            'password' => 'password123',
            'phone' => '13800138001',
            'sms_code' => '000000',
        ]);

        $response->assertSessionHasErrors('sms_code');
        $this->assertNull(User::where('email', 'smsuser2@test.dev')->first());
    }

    public function test_login_with_phone_and_password(): void
    {
        User::create([
            'name' => '手机号用户', 'email' => 'phoneuser@test.dev',
            'password' => bcrypt('secret123'), 'phone' => '13900139000',
            'status' => 1, 'plan_id' => 'free',
        ]);

        $response = $this->post('/login', [
            'email' => '13900139000',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('phone', '13900139000')->first());
    }

    /**
     * 安全审计周期 #14：手机号登录失败锁定绕过修复
     * 邮箱路径锁定期间正确凭证也被拒绝；手机路径此前缺 blocked 前置检查，
     * 锁定期间正确密码/验证码可直接登录（暴力破解防护被绕过）
     */
    public function test_locked_phone_login_rejects_correct_password(): void
    {
        User::create([
            'name' => '锁定用户', 'email' => 'locked@test.dev',
            'password' => bcrypt('secret123'), 'phone' => '13611112222',
            'status' => 1, 'plan_id' => 'free',
        ]);

        // 默认 5 次失败触发锁定
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => '13611112222',
                'password' => 'wrong-password',
            ]);
        }

        // 锁定期间正确密码也必须被拒绝
        $response = $this->post('/login', [
            'email' => '13611112222',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_locked_phone_login_rejects_valid_sms_code(): void
    {
        User::create([
            'name' => '锁定验证码用户', 'email' => 'lockedcode@test.dev',
            'password' => bcrypt('secret123'), 'phone' => '13633334444',
            'status' => 1, 'plan_id' => 'free',
        ]);

        SmsService::send('13633334444', 'login');
        $code = $this->codeFromCache('13633334444', 'login');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => '13633334444',
                'password' => 'wrong-password',
            ]);
        }

        // 锁定期间正确验证码也必须被拒绝
        $response = $this->post('/login', [
            'email' => '13633334444',
            'sms_code' => $code,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** 显式回归：被封禁用户（status!==1）即使凭证正确也不得登录 */
    public function test_disabled_user_cannot_login_by_phone(): void
    {
        User::create([
            'name' => '封禁用户', 'email' => 'banned@test.dev',
            'password' => bcrypt('secret123'), 'phone' => '13555556666',
            'status' => 0, 'plan_id' => 'free',
        ]);

        $response = $this->post('/login', [
            'email' => '13555556666',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_with_phone_and_sms_code(): void
    {
        $user = User::create([
            'name' => '验证码用户', 'email' => 'codeuser@test.dev',
            'password' => bcrypt('secret123'), 'phone' => '13700137000',
            'status' => 1, 'plan_id' => 'free',
        ]);

        SmsService::send('13700137000', 'login');
        $code = $this->codeFromCache('13700137000', 'login');

        // 不带密码，仅手机号 + 验证码登录
        $response = $this->post('/login', [
            'email' => '13700137000',
            'sms_code' => $code,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_phone_rejects_wrong_code(): void
    {
        User::create([
            'name' => '错误码用户', 'email' => 'badcode@test.dev',
            'password' => bcrypt('secret123'), 'phone' => '13600136000',
            'status' => 1, 'plan_id' => 'free',
        ]);

        SmsService::send('13600136000', 'login');

        $response = $this->post('/login', [
            'email' => '13600136000',
            'sms_code' => '111111',
        ]);

        $response->assertSessionHasErrors('sms_code');
        $this->assertGuest();
    }

    public function test_forgot_password_via_sms_resets_password(): void
    {
        $user = User::create([
            'name' => '找回用户', 'email' => 'forgot@test.dev',
            'password' => bcrypt('oldpassword'), 'phone' => '13500135000',
            'status' => 1, 'plan_id' => 'free',
        ]);

        // 第一步：提交手机号 → 发码并跳转短信重置页
        $response = $this->post('/forgot-password', ['email' => '13500135000']);
        $response->assertRedirect(route('password.reset_sms'));

        $code = $this->codeFromCache('13500135000', 'forgot_password');

        // 第二步：短信重置页提交新密码
        $this->get(route('password.reset_sms'))->assertOk();

        $response = $this->post('/reset-password-by-sms', [
            'phone' => '13500135000',
            'sms_code' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));

        // 新密码可登录
        $this->post('/login', [
            'email' => '13500135000',
            'password' => 'newpassword123',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    public function test_phone_bind_in_account(): void
    {
        $user = User::create([
            'name' => '绑定用户', 'email' => 'bind@test.dev',
            'password' => bcrypt('secret123'),
            'status' => 1, 'plan_id' => 'free',
        ]);

        $this->assertNull($user->phone);

        SmsService::send('13400134000', 'phone_bind');
        $code = $this->codeFromCache('13400134000', 'phone_bind');

        $response = $this->actingAs($user)->post('/account/phone/bind', [
            'phone' => '13400134000',
            'sms_code' => $code,
        ]);

        $response->assertRedirect();
        $this->assertSame('13400134000', $user->refresh()->phone);
        $this->assertNotNull($user->refresh()->phone_verified_at);
    }

    public function test_sms_send_endpoint_rejects_unknown_phone(): void
    {
        // login purpose：手机号未注册 → 报错不发送
        $response = $this->post('/sms/send', ['phone' => '13000130000', 'purpose' => 'login']);

        $response->assertSessionHasErrors('phone');
        $this->assertFalse((bool) Cache::get('monit.sms.login.13000130000'));
    }

    /**
     * 回归：后台保存的布尔为 'true'/'false' 字符串（AdminSettings::saveSettings 约定）。
     * (bool)'false' 为 truthy，曾导致后台关闭短信后功能仍开启；须 filter_var 归一化。
     */
    public function test_string_setting_values_disable_sms(): void
    {
        // 总开关以字符串 'false' 保存（模拟后台关闭短信）
        Settings::set('sms.sms_is_enabled', 'false');
        try {
            $this->assertFalse(SmsService::isEnabled());
            $this->assertFalse(SmsService::scenarioEnabled('register'));

            // 注册页不再渲染短信手机号/验证码输入
            $this->get('/register')
                ->assertOk()
                ->assertDontSee('name="sms_code"', false)
                ->assertDontSee('name="phone"', false);

            // 总开关 'true' + 场景 'false'：场景级关闭生效
            Settings::set('sms.sms_is_enabled', 'true');
            Settings::set('sms.sms_register_is_enabled', 'false');
            $this->assertTrue(SmsService::isEnabled());
            $this->assertFalse(SmsService::scenarioEnabled('register'));
            $this->get('/register')
                ->assertOk()
                ->assertDontSee('name="sms_code"', false);

            // 两者均 'true'：字符串开启路径可用
            Settings::set('sms.sms_register_is_enabled', 'true');
            $this->assertTrue(SmsService::scenarioEnabled('register'));
            $this->get('/register')
                ->assertOk()
                ->assertSee('name="sms_code"', false);
        } finally {
            Settings::set('sms.sms_is_enabled', true);
            Settings::set('sms.sms_register_is_enabled', true);
        }
    }
}
