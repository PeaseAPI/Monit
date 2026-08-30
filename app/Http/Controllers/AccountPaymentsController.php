<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 用户支付记录控制器
 * 规格书 §6.2.5：/account-payments - 历史支付
 */
class AccountPaymentsController extends Controller
{
    public function index(): View
    {
        $payments = Payment::where('user_id', auth()->id())
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('account.payments', compact('payments'));
    }
}
