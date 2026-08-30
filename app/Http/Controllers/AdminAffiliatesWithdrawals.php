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
        $withdrawal->update(['status' => 'approved', 'processed_datetime' => now()]);

        return back()->with('success', __('msg.withdrawal_approved'));
    }

    public function reject(int $withdrawalId): RedirectResponse
    {
        $withdrawal = AffiliateWithdrawal::findOrFail($withdrawalId);
        $withdrawal->update(['status' => 'rejected', 'processed_datetime' => now()]);

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
