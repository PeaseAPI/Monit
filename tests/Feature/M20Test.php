<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\Website;
use App\Services\Ai\AiService;
use App\Services\Payment\PaymentService;
use App\Support\Currency;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M20 多货币体系 + AI 接入验证（规格书 §10.4 / §12.6）
 * - §10.4：默认支付货币 CNY；后台任意货币 + 汇率；计划价直配/换算回退链；无价不得下单
 * - §12.6：AI 设置组；统一 chat 服务（log 调试驱动）；统计页 AI 洞察端点门控
 */
class M20Test extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    protected function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'M20 用户', 'email' => 'm20@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ], $attrs));
    }

    /* ---------------- §10.4 多货币 ---------------- */

    public function test_default_currency_is_cny(): void
    {
        $this->assertSame('CNY', Currency::default());
        $this->assertSame('CNY', Currency::normalize(''));   // 空值回退默认
        $this->assertSame('CNY', Currency::normalize('xxx')); // 非法代码回退默认
        $this->assertSame('USD', Currency::normalize('usd')); // 合法代码规范化大写
        $this->assertSame(1.0, Currency::rate('CNY'));        // 基准恒为 1
        $this->assertSame(0.14, Currency::rate('USD'));
    }

    public function test_custom_currency_from_settings_with_conversion(): void
    {
        Settings::set('payment.currencies', json_encode([
            'AUD' => ['name' => '澳元', 'symbol' => 'A$', 'rate' => 0.21],
        ], JSON_UNESCAPED_UNICODE));

        $all = Currency::all();
        $this->assertArrayHasKey('CNY', $all);
        $this->assertArrayHasKey('AUD', $all);
        $this->assertSame('CNY', array_key_first($all)); // 默认货币置顶
        $this->assertSame(0.21, Currency::rate('AUD'));
        $this->assertSame('A$', Currency::symbol('AUD'));

        // 扁平形态（默认货币定价）→ 换算：100 CNY = 21.00 AUD
        $this->assertSame(21.0, Currency::planPrice(['monthly' => 100], 'AUD', 'monthly'));

        // 嵌套直配价优先于换算
        $this->assertSame(
            12.5,
            Currency::planPrice(['AUD' => ['monthly' => 12.5], 'CNY' => ['monthly' => 100]], 'AUD', 'monthly')
        );

        // annual 兼容 yearly 键
        $this->assertSame(
            252.0,
            Currency::planPrice(['yearly' => 1200], 'AUD', 'annual')
        );

        // 跨汇率换算：USD 直配价 → CNY（9.99 / 0.14 = 71.36）
        $this->assertSame(71.36, Currency::planPrice(['USD' => ['monthly' => 9.99]], 'CNY', 'monthly'));

        // 无任何可用价 → null（禁止 0 元下单）
        $this->assertNull(Currency::planPrice([], 'CNY', 'monthly'));
        $this->assertNull(Currency::planPrice(['USD' => ['annual' => 1]], 'CNY', 'monthly'));
    }

    public function test_create_order_uses_default_currency_and_converts(): void
    {
        $user = $this->makeUser(['email' => 'order@example.com']);
        $plan = Plan::create([
            'plan_id' => 'pro', 'name' => 'Pro', 'order' => 1, 'is_enabled' => true,
            'prices' => ['USD' => ['monthly' => 9.99, 'annual' => 99.99, 'lifetime' => 199.99]],
        ]);

        $order = app(PaymentService::class)->createOrder($user, $plan, 'offline', 'monthly');

        // 新用户无历史支付货币 → 默认 CNY，USD 价按汇率换算
        $this->assertSame('CNY', $order['currency']);
        $this->assertSame(71.36, $order['amount']);

        // 用户历史支付货币为 USD → 直接 USD 价
        $user->update(['payment_currency' => 'USD']);
        $order = app(PaymentService::class)->createOrder($user, $plan, 'offline', 'monthly');
        $this->assertSame('USD', $order['currency']);
        $this->assertSame(9.99, $order['amount']);
    }

    public function test_create_order_without_price_throws(): void
    {
        $user = $this->makeUser(['email' => 'noprice@example.com']);
        $plan = Plan::create([
            'plan_id' => 'empty', 'name' => 'Empty', 'order' => 2, 'is_enabled' => true,
            'prices' => [],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('plan_price_missing');

        app(PaymentService::class)->createOrder($user, $plan, 'offline', 'monthly');
    }

    public function test_landing_page_lists_custom_currency(): void
    {
        Settings::set('payment.currencies', json_encode([
            'AUD' => ['name' => '澳元', 'symbol' => 'A$', 'rate' => 0.21],
        ], JSON_UNESCAPED_UNICODE));

        Plan::create([
            'plan_id' => 'pro', 'name' => 'Pro', 'order' => 1, 'is_enabled' => true,
            'prices' => ['CNY' => ['monthly' => 19.9], 'USD' => ['monthly' => 9.99]],
        ]);

        // 自定义货币进入切换器并可切换（session 持久化）
        $this->get('/?currency=AUD')
            ->assertOk()
            ->assertSee('<option value="AUD" selected', false);

        $this->get('/')->assertOk()->assertSee('<option value="AUD" selected', false);

        // 未配置的货币代码被忽略，保留当前会话选择（AUD）
        $this->get('/?currency=XYZ')->assertOk()->assertSee('<option value="AUD" selected', false);
    }

    public function test_admin_payment_group_saves_currencies(): void
    {
        $admin = $this->makeUser(['email' => 'admin@m20.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            '_token' => csrf_token(),
            'group' => 'payment',
            'currency' => 'CNY',
            'currencies' => [
                'AUD' => ['name' => '澳元', 'symbol' => 'A$', 'rate' => 0.21, 'code_display' => 'AUD'],
                'CNY' => ['name' => '人民币', 'symbol' => '¥', 'rate' => 1], // 默认货币行应被剔除
                'TOOLONG' => ['name' => 'x', 'symbol' => '', 'rate' => 1],   // 非 3 字母代码应被剔除
            ],
        ])->assertRedirect();

        $stored = Settings::get('payment.currencies');
        $stored = is_string($stored) ? json_decode($stored, true) : $stored;
        $this->assertSame(['AUD'], array_keys($stored));
        $this->assertSame(0.21, (float) $stored['AUD']['rate']);
    }

    /* ---------------- §12.6 AI 接入 ---------------- */

    public function test_admin_settings_ai_group_saves(): void
    {
        $admin = $this->makeUser(['email' => 'ai-admin@m20.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            '_token' => csrf_token(),
            'group' => 'ai',
            'ai_is_enabled' => '1',
            'ai_provider' => 'aliyun_bailian',
            'ai_api_key' => 'sk-test-123',
            'ai_model' => 'qwen-max',
            'ai_base_url' => '',
            'ai_temperature' => '0.5',
            'ai_max_tokens' => '2048',
            'ai_timeout' => '30',
            'ai_insights_is_enabled' => '1',
        ])->assertRedirect();

        $this->assertSame('true', Settings::get('ai.ai_is_enabled'));
        $this->assertSame('aliyun_bailian', Settings::get('ai.ai_provider'));
        $this->assertSame('qwen-max', AiService::model());
        $this->assertTrue(AiService::isConfigured());
        $this->assertSame('https://dashscope.aliyuncs.com/compatible-mode/v1', AiService::baseUrl());

        // 非法服务商被校验拒绝
        $this->actingAs($admin)->put('/admin/settings', [
            '_token' => csrf_token(),
            'group' => 'ai',
            'ai_provider' => 'openai',
        ])->assertInvalid('ai_provider');
    }

    public function test_ai_log_driver_chat(): void
    {
        Settings::set('ai.ai_is_enabled', true);
        Settings::set('ai.ai_provider', 'log');

        $result = AiService::chat('近 7 天流量如何？', '你是数据分析师');

        $this->assertTrue($result['ok']);
        $this->assertSame('log', $result['provider']);
        $this->assertStringContainsString('monit-ai:log', $result['content']);
        $this->assertStringContainsString('近 7 天流量如何', $result['content']);

        // 未启用时直接报 ai_disabled
        Settings::set('ai.ai_is_enabled', false);
        $this->assertFalse(AiService::chat('x')['ok']);
        $this->assertSame('ai_disabled', AiService::chat('x')['error']);
    }

    public function test_stats_ai_insight_endpoint_gating(): void
    {
        $user = $this->makeUser(['email' => 'insight@m20.dev']);
        $website = Website::create([
            'user_id' => $user->user_id, 'pixel_key' => 'px_ai_m20',
            'name' => 'AI 站点', 'scheme' => 'https', 'host' => 'ai.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        // 未启用 → 403
        $this->actingAs($user)->postJson("/stats/{$website->website_id}/ai-insight", ['range' => 7])
            ->assertStatus(403);

        // 启用（log 驱动）→ 200 + insight 内容
        Settings::set('ai.ai_is_enabled', true);
        Settings::set('ai.ai_provider', 'log');
        Settings::set('ai.ai_insights_is_enabled', true);

        $response = $this->actingAs($user)->postJson("/stats/{$website->website_id}/ai-insight", ['range' => 7]);
        $response->assertOk();
        $this->assertSame('log', $response->json('provider'));
        $this->assertStringContainsString('网站「AI 站点」', $response->json('insight'));

        // 非本人网站 → 403（can:own）
        $other = $this->makeUser(['email' => 'other@m20.dev']);
        $this->actingAs($other)->postJson("/stats/{$website->website_id}/ai-insight", ['range' => 7])
            ->assertStatus(403);
    }
}
