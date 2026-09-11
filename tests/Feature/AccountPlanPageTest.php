<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 规格书 §6.2.5：/account-plan 套餐卡价格展示
 * - 月价经 Currency::planPrice（用户支付货币 + 回退链）格式化展示
 * - 价格位不得输出原始 prices JSON（此前误用 @json($plan->prices) 于文本位）
 */
class AccountPlanPageTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::create([
            'name' => '套餐页测试用户', 'email' => uniqid('plan-').'@plan.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ]);
    }

    protected function makePlan(): Plan
    {
        return Plan::create([
            'plan_id' => 'starter-'.uniqid(),
            'name' => 'Starter',
            'prices' => ['CNY' => ['monthly' => 29, 'annual' => 290, 'lifetime' => 580]],
            'settings' => ['websites_limit' => 5],
            'order' => 1,
            'is_enabled' => true,
        ]);
    }

    public function test_plan_card_renders_formatted_monthly_price(): void
    {
        $this->makePlan();

        $this->actingAs($this->makeUser())->get('/account-plan')
            ->assertOk()
            // 格式化月价（默认货币 CNY），而非原始 JSON
            ->assertSee('29.00', false)
            ->assertDontSee('{"CNY"', false);
    }

    public function test_priceless_plan_card_renders_free_label(): void
    {
        Plan::create([
            'plan_id' => 'free-'.uniqid(),
            'name' => 'Free',
            'prices' => [],
            'settings' => ['websites_limit' => 1],
            'order' => 0,
            'is_enabled' => true,
        ]);

        $this->actingAs($this->makeUser())->get('/account-plan')
            ->assertOk()
            ->assertSee(__('account.free_plan'), false)
            ->assertDontSee('{"CNY"', false);
    }
}
