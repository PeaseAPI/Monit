<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /plan 套餐页价格渲染（修复：$plan->price 不存在属性导致恒 ¥0.00 + PHP 8.3 deprecation）
 */
class PlanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_付费套餐显示真实价格而非零(): void
    {
        Plan::create([
            'plan_id' => 'pro', 'name' => 'Pro', 'order' => 1, 'is_enabled' => true,
            'prices' => ['CNY' => ['monthly' => 29.9, 'annual' => 299]],
            'settings' => ['websites_limit' => 5],
        ]);

        // Currency::format：非 ASCII 符号（¥）置于金额后
        $this->get('/plan')
            ->assertOk()
            ->assertSee('29.90')
            ->assertSee(__('plan.max_websites', ['count' => 5]))
            ->assertDontSee('0.00');
    }

    public function test_无价套餐显示免费(): void
    {
        Plan::create([
            'plan_id' => 'free', 'name' => 'Free', 'order' => 2, 'is_enabled' => true,
            'prices' => [],
            'settings' => ['websites_limit' => -1],
        ]);

        $this->get('/plan')
            ->assertOk()
            ->assertSee(__('landing.free'))
            ->assertSee(__('landing.feat_websites_unlimited'));
    }

    public function test_settings_websites_limit_缺失回落零而非无限(): void
    {
        Plan::create([
            'plan_id' => 'bare', 'name' => 'Bare', 'order' => 3, 'is_enabled' => true,
            'prices' => ['CNY' => ['monthly' => 1]],
            'settings' => [],
        ]);

        // settings 无 websites_limit → 不应渲染为"不限"（旧代码 $plan->max_websites 恒 null → ∞）
        $this->get('/plan')
            ->assertOk()
            ->assertSee(__('plan.max_websites', ['count' => 0]))
            ->assertDontSee(__('landing.feat_websites_unlimited'));
    }
}
