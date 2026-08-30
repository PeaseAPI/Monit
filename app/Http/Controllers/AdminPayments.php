<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentAudit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 支付管理
 * 规格书 §6.3.3：AdminPayments / AdminPaymentCreate
 */
class AdminPayments extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('user');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"));
        }

        $payments = $query->orderByDesc('datetime')->paginate(50);

                return view('admin.payments.index', compact('payments'))->with('adminNav', 'payments');
    }

    public function create()
    {
        $users = User::where('status', 1)->orderBy('name')->limit(1000)->pluck('name', 'user_id');

                return view('admin.payments.create', compact('users'))->with('adminNav', 'payments');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
            'total_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_processor' => ['required', 'string', 'max:64'],
            'type' => ['required', 'in:one_time,subscription'],
            'plan_id' => ['nullable', 'string', 'max:64', 'exists:plans,plan_id'],
        ]);

        $user = User::find($validated['user_id']);

        $payment = Payment::create([
            ...$validated,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 1,
            'frequency' => $validated['type'] === 'subscription' ? 'monthly' : 'one_time',
        ]);

        return redirect()->route('admin.payments.index')
                        ->with('success', __('msg.payment_created'));
    }

    public function view(int $paymentId)
    {
        $payment = Payment::with('user')->findOrFail($paymentId);
        $auditLogs = PaymentAudit::where('payment_id', $paymentId)->orderByDesc('datetime')->get();

                return view('admin.payments.view', compact('payment', 'auditLogs'))->with('adminNav', 'payments');
    }

    /**
     * 联盟提现管理列表（规格书 §14.7）
     */
    public function affiliateWithdrawals(Request $request)
    {
        $query = \App\Models\AffiliateWithdrawal::with('user');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $withdrawals = $query->orderByDesc('datetime')->paginate(50);

        return view('admin.affiliates.withdrawals', compact('withdrawals'))->with('adminNav', 'affiliates');
    }

    /**
     * 审批通过联盟提现
     */
    public function approveWithdrawal(int $withdrawalId): RedirectResponse
    {
        $withdrawal = \App\Models\AffiliateWithdrawal::findOrFail($withdrawalId);

        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['status' => __('referrals.withdrawal_not_pending')]);
        }

        $withdrawal->update([
            'status' => 'approved',
        ]);

        return back()->with('success', __('referrals.withdrawal_approved'));
    }

    /**
     * 拒绝联盟提现
     */
    public function rejectWithdrawal(int $withdrawalId): RedirectResponse
    {
        $withdrawal = \App\Models\AffiliateWithdrawal::findOrFail($withdrawalId);

        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['status' => __('referrals.withdrawal_not_pending')]);
        }

        $withdrawal->update([
            'status' => 'rejected',
        ]);

        return back()->with('success', __('referrals.withdrawal_rejected'));
    }
}
