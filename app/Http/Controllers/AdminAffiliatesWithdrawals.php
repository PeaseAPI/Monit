<?php

namespace App\Http\Controllers;

use App\Models\AffiliateWithdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 联盟提现审核（规格书 §6.3.3：AdminAffiliatesWithdrawals）
 */
class AdminAffiliatesWithdrawals extends Controller
{
    public function index(Request $request): View
    {
        $query = AffiliateWithdrawal::with('user')->orderByDesc('datetime');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $withdrawals = $query->paginate(25);

        return view('admin.affiliates.withdrawals', compact('withdrawals'))->with('adminNav', 'affiliates');
    }

    public function approve(int $withdrawalId): RedirectResponse
    {
        $withdrawal = AffiliateWithdrawal::findOrFail($withdrawalId);

        // 状态机：仅 pending 可审批（安全审计周期 #16）。对齐同模型的
        // AdminPayments::approveWithdrawal 入口与 bulkUpdate 的
        // where('status','pending')，防止已终态提现被翻转/重复审批
        // （重复支付风险）。注：processed_datetime 此前指向不存在的列
        // 且不在 fillable，属静默丢弃的死代码，一并移除
        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['status' => __('referrals.withdrawal_not_pending')]);
        }

        $withdrawal->update(['status' => 'approved']);

        return back()->with('success', __('msg.withdrawal_approved'));
    }

    public function reject(int $withdrawalId): RedirectResponse
    {
        $withdrawal = AffiliateWithdrawal::findOrFail($withdrawalId);

        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['status' => __('referrals.withdrawal_not_pending')]);
        }

        $withdrawal->update(['status' => 'rejected']);

        return back()->with('success', __('msg.withdrawal_rejected'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $method = $validated['action'] === 'approve' ? 'approved' : 'rejected';
        AffiliateWithdrawal::whereIn('id', $validated['ids'])
            ->where('status', 'pending')
            ->update(['status' => $method, 'processed_datetime' => now()]);

        return back()->with('success', __('msg.bulk_update_success'));
    }
}
