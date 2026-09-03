<?php

namespace Tests\Feature;

use App\Jobs\SendBroadcastEmail;
use App\Jobs\SendEmailReport;
use App\Mail\ActivateUser;
use App\Mail\PlanDowngraded;
use App\Mail\ResetPassword;
use App\Models\Broadcast;
use App\Models\User;
use App\Models\Website;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 邮件通知链路接线（注册激活 / 密码重置 / 广播 / 邮件报告 / 降级通知 / cron 委托）
 */
class MailLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => '测试用户', 'email' => 'user@test.dev',
            'password' => bcrypt('password123'), 'status' => 1,
            'plan_id' => 'free', 'plan_settings' => [],
        ], $attrs));
    }

    /* ---------------- 注册激活链路 ---------------- */

    public function test_register_activates_immediately_when_disabled(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => '直开用户', 'email' => 'direct@test.dev',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $user = User::where('email', 'direct@test.dev')->first();
        $this->assertSame(1, $user->status);
        $this->assertNull($user->email_activation_code);

        // 免激活注册：不发激活邮件，但发送欢迎邮件（users.welcome_email_is_enabled 默认开）
        Mail::assertSent(\App\Mail\WelcomeUser::class);
        Mail::assertNotSent(\App\Mail\ActivateUser::class);
    }

    public function test_register_sends_activation_email_when_enabled(): void
    {
        Mail::fake();
        Settings::set('users.email_activation_is_enabled', 'true');

        $this->post('/register', [
            'name' => '待激活', 'email' => 'pending@test.dev',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('activation.sent'));

        // 待激活用户不能直接登录
        $this->assertGuest();

        $user = User::where('email', 'pending@test.dev')->first();
        $this->assertSame(0, $user->status);
        $this->assertNotNull($user->email_activation_code);

        Mail::assertSent(ActivateUser::class, 1);
        Mail::assertSent(ActivateUser::class, fn (ActivateUser $mail) => $mail->activationUrl === route('activation.activate', $user->email_activation_code));

        // 点击邮件链接 → 激活成功
        $this->get(route('activation.activate', $user->email_activation_code))
            ->assertRedirect(route('login'));

        $this->assertSame(1, $user->refresh()->status);
        $this->assertNull($user->refresh()->email_activation_code);
    }

    public function test_resend_activation_regenerates_code_and_sends(): void
    {
        Mail::fake();
        $user = $this->makeUser(['email' => 'resend@test.dev', 'status' => 0, 'email_activation_code' => 'old-code-123']);

        $this->post('/resend-activation', ['email' => $user->email])
            ->assertRedirect(route('activation.sent'));

        $this->assertNotSame('old-code-123', $user->refresh()->email_activation_code);
        Mail::assertSent(ActivateUser::class, 1);

        // 已激活用户不再发送
        $this->post('/resend-activation', ['email' => $this->makeUser(['email' => 'active@test.dev'])->email]);
        Mail::assertSent(ActivateUser::class, 1);
    }

    /* ---------------- 密码重置链路 ---------------- */

    public function test_forgot_password_sends_reset_email(): void
    {
        Mail::fake();
        $user = $this->makeUser(['email' => 'reset@test.dev']);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        $code = $user->refresh()->lost_password_code;
        $this->assertNotNull($code);

        Mail::assertSent(ResetPassword::class, 1);
        Mail::assertSent(ResetPassword::class, fn (ResetPassword $mail) => $mail->resetUrl === route('password.reset', $code));

        // 未知邮箱：提示一致但不发送（不暴露注册状态）
        $this->post('/forgot-password', ['email' => 'ghost@test.dev'])
            ->assertSessionHas('status');

        Mail::assertSent(ResetPassword::class, 1);
    }

    public function test_reset_password_via_email_code(): void
    {
        // 周期 #18 起重置码 60 分钟过期，测试数据需携带签发时间
        $user = $this->makeUser([
            'email' => 'reset2@test.dev',
            'lost_password_code' => 'valid-code-64',
            'lost_password_sent_at' => now(),
        ]);

        $this->get(route('password.reset', 'valid-code-64'))->assertOk();

        $this->post('/reset-password', [
            'code' => 'valid-code-64',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(password_verify('newpassword123', $user->refresh()->password));
        $this->assertNull($user->refresh()->lost_password_code);
    }

    /* ---------------- 广播邮件命令 ---------------- */

    public function test_process_broadcasts_dispatches_per_recipient(): void
    {
        Queue::fake();

        $admin = $this->makeUser(['email' => 'owner@test.dev']);
        $this->makeUser(['email' => 'a@test.dev']);
        $this->makeUser(['email' => 'b@test.dev']);
        $this->makeUser(['email' => 'banned@test.dev', 'status' => 0]); // 禁用用户不发送

        $broadcast = Broadcast::create([
            'user_id' => $admin->user_id,
            'title' => '测试广播', 'content' => '内容',
            'status' => 'pending', 'target' => 'all',
            'scheduled_at' => now()->subMinute(), 'datetime' => now(),
        ]);

        $this->artisan('monit:process-broadcasts')->assertSuccessful();

        Queue::assertPushed(SendBroadcastEmail::class, 3);

        $broadcast->refresh();
        $this->assertSame('sent', $broadcast->status);
        $this->assertSame(3, $broadcast->total_emails);
    }

    /* ---------------- 邮件报告命令 ---------------- */

    public function test_send_email_reports_dispatches_job(): void
    {
        Queue::fake();

        $user = $this->makeUser();
        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_report',
            'name' => 'Report', 'scheme' => 'https', 'host' => 'report.test',
            'tracking_type' => 'lightweight', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
            'email_reports_is_enabled' => true,
        ]);

        $this->artisan('monit:send-email-reports')->assertSuccessful();

        Queue::assertPushed(SendEmailReport::class, 1);

        $this->assertNotNull($website->refresh()->email_reports_last_date);
    }

    /* ---------------- 套餐降级通知 ---------------- */

    public function test_plan_expiration_command_notifies_downgraded_users(): void
    {
        Mail::fake();

        $user = $this->makeUser([
            'plan_id' => 'custom', 'plan_expiration_date' => now()->subDay(),
        ]);

        $this->artisan('monit:users-plan-expiration')->assertSuccessful();

        $this->assertSame('free', $user->refresh()->plan_id);
        Mail::assertQueued(PlanDowngraded::class, 1);
    }

    /* ---------------- Cron 端点委托（TypeError / 非法枚举值回归） ---------------- */

    public function test_cron_endpoints_delegate_to_commands(): void
    {
        Queue::fake();
        config(['app.cron_key' => 'secret-key-123']);

        $this->getJson('/cron/broadcasts?key=secret-key-123')
            ->assertOk()
            ->assertJsonPath('task', 'broadcasts');

        $this->getJson('/cron/email_reports?key=secret-key-123')
            ->assertOk()
            ->assertJsonPath('task', 'email_reports');

        $this->getJson('/cron?key=secret-key-123')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }
}
