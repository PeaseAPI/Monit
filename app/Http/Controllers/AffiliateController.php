<?php

namespace App\Http\Controllers;

use App\Models\AffiliateWithdrawal;
use App\Models\User;
use App\Support\Currency;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $user = $this->user();
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

        // 可提现余额：口径统一走 getAvailableBalance（基数 − approved −
        // pending），与申请校验完全一致（此前展示不扣 approved，与申请
        // 侧互相矛盾）
        $commissionBalance = $this->getAvailableBalance($user);
        $commissionCurrency = Currency::normalize($user->payment_currency);

        // 首页最近提现记录（完整列表在 withdrawals 页）
        $recentWithdrawals = AffiliateWithdrawal::where('user_id', $user->user_id)
            ->orderByDesc('datetime')
            ->limit(5)
            ->get();

        return view('referrals.index', compact(
            'referralKey', 'referralUrl', 'referrals',
            'commissionPercentage', 'totalReferrals', 'convertedReferrals',
            'totalCommission', 'pendingWithdrawals',
            'commissionBalance', 'commissionCurrency', 'recentWithdrawals'
        ));
    }

    /**
     * 提现申请
     */
    public function requestWithdrawal(Request $request): RedirectResponse
    {
        $this->ensureAffiliateEnabled();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'decimal:0,2'],
            'note' => ['nullable', 'string', 'max:1024'],
        ]);

        $user = $this->user();

        // 事务 + 行锁（安全审计周期 #9）：与佣金基数入账
        // （settlePayment 对 payment_total_amount 的累计）及并发申请
        // 串行化——此前申请侧校验不扣 pending，同一余额可重复申请
        // 超额提现（双击/并发即可超发）
        [$ok] = DB::transaction(function () use ($user, $validated) {
            $locked = User::where('user_id', $user->user_id)->lockForUpdate()->first();

            if ($locked === null) {
                return [false];
            }

            $available = $this->getAvailableBalance($locked);

            if ($validated['amount'] > $available) {
                return [false];
            }

            AffiliateWithdrawal::create([
                'user_id' => $user->user_id,
                'amount' => $validated['amount'],
                'currency' => Currency::normalize($locked->payment_currency),
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
                'datetime' => now(),
            ]);

            return [true];
        });

        if (! $ok) {
            return back()->withErrors(['amount' => __('referrals.insufficient_balance')]);
        }

        return back()->with('success', __('referrals.withdrawal_requested'));
    }

    /**
     * 提现记录
     */
    public function withdrawals(Request $request): View
    {
        $this->ensureAffiliateEnabled();

        $withdrawals = AffiliateWithdrawal::where('user_id', $this->user()->user_id)
            ->orderByDesc('datetime')
            ->paginate(20);

        return view('referrals.withdrawals', compact('withdrawals'));
    }

    /**
     * 计算可用余额
     */
    protected function getAvailableBalance(User $user): float
    {
        // 口径 = 佣金基数 − 已批准提现 − 待审提现（pending 占额）。
        // 此前两侧口径互不一致：申请侧不扣 pending（同一余额可重复
        // 申请超额）、首页展示侧不扣 approved（余额虚低）——统一到
        // 本 helper，申请校验与展示共用
        $withdrawn = AffiliateWithdrawal::where('user_id', $user->user_id)
            ->whereIn('status', ['approved', 'pending'])
            ->sum('amount');

        return max(0, (float) ($user->payment_total_amount ?? 0) - (float) $withdrawn);
    }
}
