<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 订阅管理控制器
 * 规格书 §6.2.6：/pay-billing - 取消/变更订阅
 */
class PayBillingController extends Controller
{
    /**
     * 订阅管理页面
     */
    public function index(): View
    {
        $user = auth()->user();

        return view('pay.billing', compact('user'));
    }

    /**
     * 取消订阅
     */
    public function cancel(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->payment_subscription_id) {
            return back()->withErrors(['error' => __('msg.no_active_subscription')]);
        }

        // 调用对应支付处理器取消订阅
        $processor = $user->payment_processor;
        $paymentService = app(PaymentService::class);

        try {
            $paymentService->cancelSubscription($user, $processor);

            return back()->with('success', __('msg.subscription_cancelled'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
