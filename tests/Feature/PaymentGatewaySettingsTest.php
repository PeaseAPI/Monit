<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EnvWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M25 支付网关密钥后台配置（写入 .env）验证
 * - 白名单：仅 PaymentGatewayCatalog 登记键可写（APP_KEY/DB_* 注入被忽略）
 * - EnvWriter：值转义（# 引号 空格 换行）、多行旧值整体替换、空值删除、非法键拒绝
 * - 视图：设置页渲染 21 网关面板 + 验签状态徽章
 */
class PaymentGatewaySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected string $tmpEnv;

    protected function setUp(): void
    {
        parent::setUp();

        // 绑定 EnvWriter 到临时文件，避免污染真实 .env
        $this->tmpEnv = sys_get_temp_dir().'/monit-envwriter-test-'.uniqid().'.env';
        file_put_contents($this->tmpEnv, "APP_KEY=base64:testkey\nAPP_URL=http://localhost\n# comment line\n\nSTRIPE_KEY=sk_test_old\n");
        $this->app->instance(EnvWriter::class, new EnvWriter($this->tmpEnv));
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpEnv);
        parent::tearDown();
    }

    protected function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'M25 用户', 'email' => 'm25@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ], $attrs));
    }

    /* ---------------- 视图渲染 ---------------- */

    public function test_settings_page_renders_gateway_keys_tab(): void
    {
        $admin = $this->makeUser(['email' => 'admin@m25.dev', 'type' => 1]);

        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('payment_gateways')
            ->assertSee('STRIPE_WEBHOOK_SECRET')
            ->assertSee('PADDLE_WEBHOOK_SECRET');
    }

    public function test_non_admin_cannot_open_settings(): void
    {
        $user = $this->makeUser(['email' => 'user@m25.dev']);

        $this->actingAs($user)->get('/admin/settings')->assertStatus(403);
    }

    /* ---------------- 保存写 .env ---------------- */

    public function test_saving_writes_whitelisted_keys_to_env(): void
    {
        $admin = $this->makeUser(['email' => 'admin2@m25.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'payment_gateways',
            'STRIPE_KEY' => 'sk_live_NEWKEY',
            'STRIPE_SECRET' => 'whsec_abc#123 "quoted"',
            'RAZORPAY_WEBHOOK_SECRET' => 'rzp_sig with space',
        ])->assertSessionHas('success');

        $content = file_get_contents($this->tmpEnv);

        // 旧值被替换，新值写入
        $this->assertStringContainsString('STRIPE_KEY=sk_live_NEWKEY', $content);
        // 含 # 与引号的值被双引号包裹转义，不会截断注释或注入换行
        $this->assertStringContainsString('STRIPE_SECRET="whsec_abc#123 \\"quoted\\""', $content);
        $this->assertStringContainsString('RAZORPAY_WEBHOOK_SECRET="rzp_sig with space"', $content);
        $this->assertStringNotContainsString('sk_test_old', $content);

        // 读回语义一致
        $writer = new EnvWriter($this->tmpEnv);
        $this->assertSame('sk_live_NEWKEY', $writer->read('STRIPE_KEY'));
        $this->assertSame('whsec_abc#123 "quoted"', $writer->read('STRIPE_SECRET'));
        $this->assertSame('rzp_sig with space', $writer->read('RAZORPAY_WEBHOOK_SECRET'));
        // 非白名单键不受影响
        $this->assertSame('base64:testkey', $writer->read('APP_KEY'));
    }

    public function test_env_injection_of_arbitrary_keys_is_ignored(): void
    {
        $admin = $this->makeUser(['email' => 'admin3@m25.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'payment_gateways',
            'APP_KEY' => 'attacker-controlled-key',
            'DB_PASSWORD' => 'pwned',
            'STRIPE_KEY' => 'sk_ok',
        ])->assertSessionHas('success');

        $content = file_get_contents($this->tmpEnv);

        // 白名单外键被完全忽略
        $this->assertStringNotContainsString('attacker-controlled-key', $content);
        $this->assertStringNotContainsString('pwned', $content);
        $this->assertStringContainsString('sk_ok', $content);
        $this->assertSame('base64:testkey', (new EnvWriter($this->tmpEnv))->read('APP_KEY'));
    }

    public function test_empty_value_clears_key(): void
    {
        $admin = $this->makeUser(['email' => 'admin4@m25.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'payment_gateways',
            'STRIPE_KEY' => '',
        ])->assertSessionHas('success');

        $this->assertStringNotContainsString('STRIPE_KEY=', file_get_contents($this->tmpEnv));
        $this->assertNull((new EnvWriter($this->tmpEnv))->read('STRIPE_KEY'));
    }

    public function test_bool_key_normalized_to_true_false(): void
    {
        $admin = $this->makeUser(['email' => 'admin5@m25.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'payment_gateways',
            'PAYPAL_SANDBOX' => '1',
        ])->assertSessionHas('success');

        $this->assertStringContainsString('PAYPAL_SANDBOX=true', file_get_contents($this->tmpEnv));
    }

    public function test_oversized_value_rejected(): void
    {
        $admin = $this->makeUser(['email' => 'admin6@m25.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'payment_gateways',
            'STRIPE_KEY' => str_repeat('x', 5000),
        ])->assertSessionHasErrors('STRIPE_KEY');
    }

    /* ---------------- EnvWriter 单元 ---------------- */

    public function test_env_writer_multiline_value_roundtrip(): void
    {
        $writer = new EnvWriter($this->tmpEnv);

        $pem = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBg\n+line/with\"quote\"#hash\n-----END PRIVATE KEY-----";
        $writer->write('PADDLE_PUBLIC_KEY', $pem);

        // 写入后仍是单行记录（\n 字面转义）
        $this->assertSame(1, substr_count(file_get_contents($this->tmpEnv), 'PADDLE_PUBLIC_KEY='));
        $this->assertSame($pem, $writer->read('PADDLE_PUBLIC_KEY'));

        // 覆盖多行旧值不残留
        $writer->write('PADDLE_PUBLIC_KEY', 'short');
        $this->assertSame('short', $writer->read('PADDLE_PUBLIC_KEY'));
        $this->assertStringNotContainsString('MIIEvQIBADANBg', file_get_contents($this->tmpEnv));
    }

    public function test_env_writer_illegal_key_rejected(): void
    {
        $writer = new EnvWriter($this->tmpEnv);

        try {
            $writer->write("APP_KEY\nEVIL", 'x');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Illegal', $e->getMessage());
        }

        try {
            $writer->write('lowercase_key', 'x');
            $this->fail('Expected InvalidArgumentException for lowercase key');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Illegal', $e->getMessage());
        }
    }

    public function test_env_writer_value_cannot_inject_new_env_lines(): void
    {
        $writer = new EnvWriter($this->tmpEnv);

        // 尝试用引号闭合 + 换行注入新 env 行
        $writer->write('STRIPE_SECRET', "v\"\nEVIL_KEY=pwned");

        $content = file_get_contents($this->tmpEnv);
        // EVIL_KEY 不得作为独立 env 行出现（换行已转义）
        $this->assertSame(0, preg_match('/^EVIL_KEY=/m', $content));
        $this->assertSame("v\"\nEVIL_KEY=pwned", $writer->read('STRIPE_SECRET'));
    }

    public function test_env_writer_append_converges_blank_lines(): void
    {
        file_put_contents($this->tmpEnv, "A=1\n\n\n\n");
        $writer = new EnvWriter($this->tmpEnv);

        $writer->write('NEW_KEY', 'v');

        $content = file_get_contents($this->tmpEnv);
        // 尾部连续空行收敛为恰好一个分隔
        $this->assertMatchesRegularExpression('/A=1\n\nNEW_KEY=v\n$/', $content);
    }
}
