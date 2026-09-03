<?php

namespace Tests\Feature;

use App\Models\AffiliateWithdrawal;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 安全审计周期 #16：Admin 后台 sweep（withdrawal 状态机 + tax destroy）
 *
 * 缺陷（修复前）：
 * - AdminAffiliatesWithdrawals::approve/reject 无 pending 前置检查：
 *   已 approved/rejected 的提现可被翻转或重复审批（重复支付风险）。
 *   同模型的 AdminPayments::approveWithdrawal/rejectWithdrawal 入口有该
 *   检查，两入口行为不一致；bulkUpdate 也有 where('status','pending')
 * - AdminTaxes::destroy 用 Tax::find()：记录不存在时 null->delete() → 500
 */
class AdminWithdrawalStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@example.com',
            'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free',
            'type' => 1,
        ]);
    }

    private function withdrawal(string $status): AffiliateWithdrawal
    {
        return AffiliateWithdrawal::create([
            'user_id' => $this->admin->user_id,
            'amount' => 100.50, 'currency' => 'USD',
            'status' => $status, 'datetime' => now(),
        ]);
    }

    public function test_pending_withdrawal_can_be_approved(): void
    {
        $w = $this->withdrawal('pending');

        $this->actingAs($this->admin)
            ->put("/admin/affiliates-withdrawals/{$w->affiliate_withdrawal_id}/approve")
            ->assertRedirect();

        $this->assertSame('approved', $w->fresh()->status);
    }

    public function test_pending_withdrawal_can_be_rejected(): void
    {
        $w = $this->withdrawal('pending');

        $this->actingAs($this->admin)
            ->put("/admin/affiliates-withdrawals/{$w->affiliate_withdrawal_id}/reject")
            ->assertRedirect();

        $this->assertSame('rejected', $w->fresh()->status);
    }

    public function test_approved_withdrawal_cannot_be_reapproved(): void
    {
        $w = $this->withdrawal('approved');

        $response = $this->actingAs($this->admin)
            ->put("/admin/affiliates-withdrawals/{$w->affiliate_withdrawal_id}/approve");

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');

        $this->assertSame('approved', $w->fresh()->status);
    }

    public function test_rejected_withdrawal_cannot_be_flipped_to_approved(): void
    {
        $w = $this->withdrawal('rejected');

        $this->actingAs($this->admin)
            ->put("/admin/affiliates-withdrawals/{$w->affiliate_withdrawal_id}/approve")
            ->assertRedirect();

        $this->assertSame('rejected', $w->fresh()->status);
    }

    public function test_approved_withdrawal_cannot_be_rejected(): void
    {
        $w = $this->withdrawal('approved');

        $this->actingAs($this->admin)
            ->put("/admin/affiliates-withdrawals/{$w->affiliate_withdrawal_id}/reject")
            ->assertRedirect();

        $this->assertSame('approved', $w->fresh()->status);
    }

    public function test_tax_destroy_missing_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->delete('/admin/taxes/99999')
            ->assertStatus(404);
    }

    public function test_tax_destroy_deletes_existing(): void
    {
        $tax = Tax::create([
            'name' => 'VAT', 'value' => 19, 'value_type' => 'percentage',
            'type' => 'inclusive', 'billing_type' => 'personal',
            'datetime' => now(),
        ]);

        $this->actingAs($this->admin)
            ->delete("/admin/taxes/{$tax->tax_id}")
            ->assertRedirect();

        $this->assertNull($tax->fresh());
    }
}
