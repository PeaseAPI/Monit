<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EnvWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 安全审计周期 #9：支付网关密钥不得明文回显（HTML 源码泄露面）
 *
 * 背景：视图曾把 .env 中的密钥完整回填进 type=password 输入框的 value，
 * type=password 只遮挡视觉显示 —— 查看源码/DevTools/浏览器缓存/单点 XSS 读取 DOM
 * 即可一次性获取全部 21 个网关的支付密钥。
 *
 * 新语义（password 类型键 / secret）：
 * - GET /admin/settings 响应不得包含密钥明文，仅掩码后 4 位（last4 行业惯例）
 * - 空值提交 = 保持不变（页面不回显，空提交是常态，防止误清空）
 * - 显式勾选 {key}__clear 复选框才清除
 *
 * 回归：text 类型键（公开 ID）维持「空值 = 清除」原语义。
 */
class PaymentGatewaySecretMaskingTest extends TestCase
{
    use RefreshDatabase;

    protected string $tmpEnv;

    protected function setUp(): void
    {
        parent::setUp();

        // 绑定 EnvWriter 到临时文件，避免污染真实 .env
        $this->tmpEnv = sys_get_temp_dir().'/monit-secret-mask-test-'.uniqid().'.env';
        file_put_contents(
            $this->tmpEnv,
            "APP_KEY=base64:testkey\nSTRIPE_KEY=sk_live_public_1234\nSTRIPE_SECRET=whsec_live_topsecret_9876\n"
        );
        $this->app->instance(EnvWriter::class, new EnvWriter($this->tmpEnv));
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpEnv);
        parent::tearDown();
    }

    protected function makeAdmin(): User
    {
        return User::create([
            'name' => '审计管理员',
            'email' => 'sec9@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => 'free',
            'type' => 1,
        ]);
    }

    /**
     * 🟡 密钥明文不得出现在设置页 HTML 中（源码/缓存/XSS 泄露面）
     */
    public function test_settings_page_does_not_echo_secret_in_html(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin/settings');

        $response->assertOk();
        $html = $response->getContent();

        // password 类型密钥明文不得出现（type=password 只挡视觉，源码即明文）
        $this->assertStringNotContainsString('whsec_live_topsecret_9876', $html);
        // 掩码提示：后 4 位可见（last4 行业惯例，供管理员核对）
        $this->assertStringContainsString('9876', $html);
        // 显式清除复选框已渲染（配套控制器 __clear 语义）
        $this->assertStringContainsString('STRIPE_SECRET__clear', $html);
    }

    /**
     * password 键空值提交 = 保持 .env 原值不变（防误清空 + 掩码下空提交属常态）
     */
    public function test_password_key_blank_submission_keeps_existing_value(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'payment_gateways',
            'STRIPE_SECRET' => '',
        ])->assertSessionHas('success');

        $this->assertSame(
            'whsec_live_topsecret_9876',
            (new EnvWriter($this->tmpEnv))->read('STRIPE_SECRET')
        );
    }

    /**
     * 勾选 {key}__clear + 空值 → 密钥被显式删除
     */
    public function test_password_key_explicit_clear_removes_it(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'payment_gateways',
            'STRIPE_SECRET' => '',
            'STRIPE_SECRET__clear' => '1',
        ])->assertSessionHas('success');

        $this->assertNull((new EnvWriter($this->tmpEnv))->read('STRIPE_SECRET'));
    }

    /**
     * 回归：text 类型键（公开 ID，页面明文回显）维持「空值 = 清除」原语义
     */
    public function test_text_key_blank_submission_still_clears(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'payment_gateways',
            'STRIPE_KEY' => '',
        ])->assertSessionHas('success');

        $writer = new EnvWriter($this->tmpEnv);
        $this->assertNull($writer->read('STRIPE_KEY'));
        // 同请求未触碰的 password 键不受影响
        $this->assertSame('whsec_live_topsecret_9876', $writer->read('STRIPE_SECRET'));
    }
}
