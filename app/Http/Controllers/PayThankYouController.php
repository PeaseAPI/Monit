<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 支付成功页控制器
 * 规格书 §6.2.6：/pay-thank-you - 支付成功回调
 */
class PayThankYouController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $plan = \App\Models\Plan::find($user->plan_id);

        return view('pay.thank_you', compact('user', 'plan'));
    }
}
