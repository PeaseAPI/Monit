<?php

namespace App\Http\Controllers;

use App\Models\AffiliateWithdrawal;
use App\Support\Typed;
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
        // 状态机：仅 pending 可审批（安全审计周期 #16）。条件原子更新将
        // 状态检查与写入合一，消除读检查→写之间的并发窗口（double-submit
        // / approve 与 reject 并发互覆）。注：表无 processed_datetime 列，
        // 不要在此写入（并发审计周期 #5 复核）
        $affected = AffiliateWithdrawal::where('affiliate_withdrawal_id', $withdrawalId)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);

        if (! $affected) {
            return back()->withErrors(['status' => __('referrals.withdrawal_not_pending')]);
        }

        return back()->with('success', __('msg.withdrawal_approved'));
    }

    public function reject(int $withdrawalId): RedirectResponse
    {
        $affected = AffiliateWithdrawal::where('affiliate_withdrawal_id', $withdrawalId)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);

        if (! $affected) {
            return back()->withErrors(['status' => __('referrals.withdrawal_not_pending')]);
        }

        return back()->with('success', __('msg.withdrawal_rejected'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = Typed::arr($request->validate([
            'action' => ['required', 'in:approve,reject'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]));

        $method = $validated['action'] === 'approve' ? 'approved' : 'rejected';
        // 修复：主键列名为 affiliate_withdrawal_id（原 whereIn('id') 对不存在的
        // 列查询必抛 500），且此前写入不存在的 processed_datetime 列——
        // 批量审批从未可用
        AffiliateWithdrawal::whereIn('affiliate_withdrawal_id', $validated['ids'])
            ->where('status', 'pending')
            ->update(['status' => $method]);

        return back()->with('success', __('msg.bulk_update_success'));
    }
}
