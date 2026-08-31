<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * M1/M8 兑换码执行链（规格 §10.3）：
 * 启用 / 有效期 / 总次数上限 / 单用户一次 / 套餐应用（含同套餐续期叠加）
 */
class CodeRedemptionTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => 'free',
        ]);
    }

    protected function makePlan(string $planId = 'pro'): Plan
    {
        return Plan::create([
            'plan_id' => $planId,
            'name' => 'Pro Plan',
            'order' => 1,
            'is_enabled' => true,
        ]);
    }

    protected function makeCode(array $overrides = []): Code
    {
        return Code::create(array_merge([
            'name' => 'Test Code',
            'code' => 'PRO2026',
            'type' => 'plan',
            'plan_id' => 'pro',
            'days' => 30,
            'is_enabled' => true,
            'datetime' => now(),
        ], $overrides));
    }

    public function test_plan_code_applies_plan_and_expiration(): void
    {
        $this->makePlan();
        $code = $this->makeCode();
        $user = $this->makeUser();

        $this->assertNull($code->redemptionIssue($user));

        $code->recordRedemption($user);
        $code->applyToUser($user);

        $user->refresh();
        $code->refresh();

        $this->assertSame('pro', $user->plan_id);
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $user->plan_expiration_date->timestamp, 5);
        $this->assertSame(1, $code->redeemed);
        $this->assertDatabaseHas('redeemed_codes', ['code_id' => $code->code_id, 'user_id' => $user->user_id]);
    }

    public function test_same_code_cannot_be_redeemed_twice_by_same_user(): void
    {
        $this->makePlan();
        $code = $this->makeCode();
        $user = $this->makeUser();

        $code->recordRedemption($user);

        $this->assertSame('account.code_already_redeemed', $code->redemptionIssue($user));
    }

    public function test_max_redemptions_limit_enforced(): void
    {
        $this->makePlan();
        $code = $this->makeCode(['max_redemptions' => 1]);
        $u1 = $this->makeUser();
        $u2 = User::create([
            'name' => 'Second', 'email' => 'second@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $code->recordRedemption($u1);
        $code->refresh();

        $this->assertSame('account.code_fully_redeemed', $code->redemptionIssue($u2));
    }

    public function test_expired_and_future_codes_rejected(): void
    {
        $this->makePlan();
        $user = $this->makeUser();

        $expired = $this->makeCode(['code' => 'OLD1', 'date_end' => now()->subDay()]);
        $this->assertSame('account.code_expired', $expired->redemptionIssue($user));

        $future = $this->makeCode(['code' => 'FUT1', 'date_start' => now()->addDay()]);
        $this->assertSame('account.code_not_yet_active', $future->redemptionIssue($user));

        $disabled = $this->makeCode(['code' => 'OFF1', 'is_enabled' => false]);
        $this->assertSame('account.invalid_code', $disabled->redemptionIssue($user));
    }

    public static function stackingScenarios(): array
    {
        return [
            'same plan future expiry stacks' => ['pro', 10, true],
            'different plan resets expiry' => ['free', 10, false],
        ];
    }

    #[DataProvider('stackingScenarios')]
    public function test_expiry_stacking_semantics(string $currentPlan, int $daysIn, bool $shouldStack): void
    {
        $this->makePlan();
        $code = $this->makeCode(['days' => $daysIn]);
        $user = $this->makeUser();

        if ($currentPlan !== 'free') {
            $user->forceFill(['plan_id' => $currentPlan])->save();
        }

        if ($shouldStack) {
            $user->forceFill(['plan_expiration_date' => now()->addDays(5)])->save();
        }

        $code->applyToUser($user);
        $user->refresh();

        $expected = $shouldStack
            ? now()->addDays(5)->addDays($daysIn)->timestamp
            : now()->addDays($daysIn)->timestamp;

        $this->assertEqualsWithDelta($expected, $user->plan_expiration_date->timestamp, 5);
    }

    public function test_redeem_endpoint_via_http(): void
    {
        $this->makePlan();
        $this->makeCode();
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/account-plan/redeem', ['code' => 'PRO2026']);

        $response->assertRedirect();
        $this->assertSame('pro', $user->refresh()->plan_id);
        $this->assertSame(1, Code::first()->redeemed);
    }
}
