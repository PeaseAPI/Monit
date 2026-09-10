<?php

namespace Tests\Feature;

use App\Models\AffiliateWithdrawal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 安全审计周期 #9：提现余额口径与并发防护
 *
 * 缺陷（修复前）：申请侧校验只扣 approved（pending 不占额）——同一
 * 余额可重复申请超额提现；展示侧只扣 pending（approved 不占额），
 * 两侧口径互相矛盾；getAvailableBalance 还有 totalEarned 死变量
 */
class AffiliateWithdrawalBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAffiliate(int $total): User
    {
        return User::factory()->create([
            'status' => 1,
            'payment_total_amount' => $total,
            'payment_currency' => 'USD',
        ]);
    }

    public function test_pending_withdrawal_counts_against_available_balance(): void
    {
        $user = $this->makeAffiliate(100);

        // 余额 100，申请 60 成功
        $this->actingAs($user)
            ->post(route('referrals.withdrawal'), ['amount' => 60])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // pending 占额后可用仅 40，再申请 60 必须被拒
        $this->actingAs($user)
            ->post(route('referrals.withdrawal'), ['amount' => 60])
            ->assertRedirect()
            ->assertSessionHasErrors(['amount']);

        $this->assertSame(60.0, (float) AffiliateWithdrawal::where('user_id', $user->user_id)->sum('amount'));
    }

    public function test_approved_and_pending_both_reduce_available_balance(): void
    {
        $user = $this->makeAffiliate(100);

        AffiliateWithdrawal::create([
            'user_id' => $user->user_id,
            'amount' => 20,
            'currency' => 'USD',
            'status' => 'approved',
            'datetime' => now(),
        ]);
        AffiliateWithdrawal::create([
            'user_id' => $user->user_id,
            'amount' => 30,
            'currency' => 'USD',
            'status' => 'pending',
            'datetime' => now(),
        ]);

        // 可用 = 100 − 20(approved) − 30(pending) = 50：申请 51 拒、50 过
        $this->actingAs($user)
            ->post(route('referrals.withdrawal'), ['amount' => 51])
            ->assertRedirect()
            ->assertSessionHasErrors(['amount']);

        $this->actingAs($user)
            ->post(route('referrals.withdrawal'), ['amount' => 50])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_rejected_withdrawals_do_not_count_against_balance(): void
    {
        $user = $this->makeAffiliate(100);

        AffiliateWithdrawal::create([
            'user_id' => $user->user_id,
            'amount' => 90,
            'currency' => 'USD',
            'status' => 'rejected',
            'datetime' => now(),
        ]);

        // rejected 不占额：仍可申请 100
        $this->actingAs($user)
            ->post(route('referrals.withdrawal'), ['amount' => 100])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_amount_more_than_two_decimals_is_rejected(): void
    {
        $user = $this->makeAffiliate(100);

        $this->actingAs($user)
            ->post(route('referrals.withdrawal'), ['amount' => 10.999])
            ->assertRedirect()
            ->assertSessionHasErrors(['amount']);
    }
}
