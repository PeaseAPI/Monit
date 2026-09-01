<?php

namespace App\Http\Controllers;

use App\Models\AffiliateWithdrawal;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 推荐返佣控制器（含联盟提现）
 * 规格书 §10.3 / §6.2.6：Referrals + Affiliate Commission
 */
class AffiliateController extends Controller
{
    /**
     * Affiliate 插件门控（规格书 §14.7：插件停用即关闭入口；默认开启保持向后兼容）
     */
    public function __construct()
    {
        $this->ensureAffiliateEnabled();
    }

    /**
     * Affiliate 插件门控（规格书 §14.7：插件停用即关闭入口；默认开启保持向后兼容）
     * 注意：Route::getController() 会缓存控制器实例，构造器可能不被重复调用，
     * 故每个入口方法都需再校验一次。
     */
    private function ensureAffiliateEnabled(): void
    {
        // 键名与后台 affiliate 组一致（affiliate.affiliate_is_enabled）；
        // 布尔以 'true'/'false' 字符串存储，须 filter_var 归一化（'false' 为 truthy）。
        if (! filter_var(Settings::get('affiliate.affiliate_is_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            abort(404);
        }
    }

    /**
     * 推荐返佣首页
     */
    public function index(Request $request): View
    {
        $this->ensureAffiliateEnabled();

        $user = $request->user();
        $referralKey = $user->referral_key;
        $referralUrl = route('register').'?ref='.$referralKey;

        $referrals = User::where('referred_by', $user->user_id)
            ->orderByDesc('user_id')
            ->paginate(20);

        $commissionPercentage = $user->getPlanSettings()['affiliate_commission_percentage'] ?? 0;

        // 统计推荐数据
        $totalReferrals = User::where('referred_by', $user->user_id)->count();
        $convertedReferrals = User::where('referred_by', $user->user_id)
            ->where('referred_by_has_converted', true)
            ->count();
        $totalCommission = $user->payment_total_amount ?? 0;
        $pendingWithdrawals = AffiliateWithdrawal::where('user_id', $user->user_id)
            ->where('status', 'pending')
            ->sum('amount');

        return view('referrals.index', compact(
            'referralKey', 'referralUrl', 'referrals',
            'commissionPercentage', 'totalReferrals', 'convertedReferrals',
            'totalCommission', 'pendingWithdrawals'
        ));
    }

    /**
     * 提现申请
     */
    public function requestWithdrawal(Request $request): RedirectResponse
    {
        $this->ensureAffiliateEnabled();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:10'],
            'note' => ['nullable', 'string', 'max:1024'],
        ]);

        $user = $request->user();

        // 检查提现金额不超过可用余额
        $availableBalance = $this->getAvailableBalance($user);
        if ($validated['amount'] > $availableBalance) {
            return back()->withErrors(['amount' => __('referrals.insufficient_balance')]);
        }

        AffiliateWithdrawal::create([
            'user_id' => $user->user_id,
            'amount' => $validated['amount'],
            'currency' => $user->payment_currency ?? 'USD',
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
            'datetime' => now(),
        ]);

        return back()->with('success', __('referrals.withdrawal_requested'));
    }

    /**
     * 提现记录
     */
    public function withdrawals(Request $request): View
    {
        $this->ensureAffiliateEnabled();

        $withdrawals = AffiliateWithdrawal::where('user_id', $request->user()->user_id)
            ->orderByDesc('datetime')
            ->paginate(20);

        return view('referrals.withdrawals', compact('withdrawals'));
    }

    /**
     * 计算可用余额
     */
    protected function getAvailableBalance(User $user): float
    {
        $totalEarned = AffiliateWithdrawal::where('user_id', $user->user_id)
            ->where('status', 'approved')
            ->sum('amount');
        $totalWithdrawn = AffiliateWithdrawal::where('user_id', $user->user_id)
            ->where('status', 'approved')
            ->sum('amount');

        return max(0, (float) ($user->payment_total_amount ?? 0) - $totalWithdrawn);
    }
}
