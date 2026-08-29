<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Services\Payment\OfflinePaymentProcessor;
use App\Services\Payment\PayPalProcessor;
use App\Services\Payment\PaymentService;
use App\Services\Payment\StripeProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 用户支付流程控制器
 * 规格书 §10/§11：套餐购买 / 支付 / 订阅
 */
class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * 支付页面 - 选择套餐和支付方式
     */
    public function index(Request $request): View
    {
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();
        $user = $request->user();

        return view('payments.index', compact('plans', 'user'));
    }

    /**
     * 发起支付
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'string', 'exists:plans,plan_id'],
            'processor' => ['required', 'in:stripe,paypal,offline'],
            'frequency' => ['required', 'in:monthly,annual,lifetime'],
            'code' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($validated['plan_id']);
        $processor = $validated['processor'];
        $frequency = $validated['frequency'];

        $order = $this->paymentService->createOrder($user, $plan, $processor, $frequency, $validated['code'] ?? null);
        $payment = Payment::find($order['payment_id']);

        return match ($processor) {
            'stripe' => $this->redirectToStripe($payment),
            'paypal' => $this->redirectToPayPal($payment),
            'offline' => $this->handleOffline($payment),
            default => back()->withErrors(['processor' => __('payment.unsupported_processor')]),
        };
    }

    /**
     * 支付成功回调页面
     */
    public function success(Request $request): View
    {
        $paymentId = $request->query('payment_id');
        $payment = $paymentId ? Payment::find($paymentId) : null;

        return view('payments.success', compact('payment'));
    }

    /**
     * 支付取消回调页面
     */
    public function cancel(): View
    {
        return view('payments.cancel');
    }

    /**
     * 兑换码页面
     */
    public function showRedeemCode(): View
    {
        return view('payments.redeem');
    }

    /**
     * 处理兑换码
     */
    public function redeemCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $result = $this->paymentService->redeemCode($request->user(), $validated['code']);

        if (! $result['success']) {
            return back()->withErrors(['code' => $result['message']]);
        }

                return back()->with('success', $result['message']);
    }

    /**
     * 上传离线支付凭证
     */
    public function uploadProof(Request $request, Payment $payment): RedirectResponse
    {
        $offlineProcessor = new OfflinePaymentProcessor();
        $result = $offlineProcessor->uploadProof($request, $payment);

        if (! ($result['success'] ?? false)) {
            return back()->withErrors(['proof' => $result['message'] ?? __('payment.proof_upload_failed')]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Stripe Webhook
     */
    public function stripeWebhook(Request $request)
    {
        $stripeProcessor = new StripeProcessor();

        if (! $stripeProcessor->verifyWebhook($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $stripeProcessor->parseWebhookEvent($request);

        if ($event['event'] === 'payment_success' && isset($event['payment_id'])) {
            $this->paymentService->handlePaymentSuccess(
                (int) $event['payment_id'],
                $event['external_id'],
                $event['subscription_id'] ?? null
            );
        }

        return response()->json(['received' => true]);
    }

    /**
     * PayPal Webhook
     */
    public function paypalWebhook(Request $request)
    {
        $payPalProcessor = new PayPalProcessor();

        if (! $payPalProcessor->verifyWebhook($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventType = $request->input('event_type', '');

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $request->input('resource', []);
            $paymentId = $resource['custom_id'] ?? null;
            $externalId = $resource['id'] ?? null;

            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, $externalId);
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * 用户支付历史
     */
    public function history(Request $request): View
    {
        $payments = $request->user()->payments()
            ->orderByDesc('datetime')
            ->paginate(20);

        return view('payments.history', compact('payments'));
    }

    /* -----------------------------------------------------------------
     | 私有方法
     ----------------------------------------------------------------- */

    protected function redirectToStripe(Payment $payment): RedirectResponse
    {
        $stripeProcessor = new StripeProcessor();

        if (! $stripeProcessor->isConfigured()) {
            return back()->withErrors(['processor' => __('payment.stripe_not_configured')]);
        }

        $result = $stripeProcessor->createCheckoutSession(
            $payment,
            route('payments.success', ['payment_id' => $payment->payment_id]),
            route('payments.cancel')
        );

        if (isset($result['error'])) {
            return back()->withErrors(['processor' => $result['error']]);
        }

        return redirect()->route('payments.stripe-checkout', ['payment' => $payment->payment_id])
            ->with('stripe_session', $result);
    }

    protected function redirectToPayPal(Payment $payment): RedirectResponse
    {
        $payPalProcessor = new PayPalProcessor();

        if (! $payPalProcessor->isConfigured()) {
            return back()->withErrors(['processor' => __('payment.paypal_not_configured')]);
        }

        $result = $payPalProcessor->createOrder(
            $payment,
            route('payments.success', ['payment_id' => $payment->payment_id]),
            route('payments.cancel')
        );

        if (isset($result['error'])) {
            return back()->withErrors(['processor' => $result['error']]);
        }

        if ($result['approve_url'] ?? null) {
            return redirect($result['approve_url']);
        }

        return back()->withErrors(['processor' => __('payment.paypal_order_failed')]);
    }

    protected function handleOffline(Payment $payment): RedirectResponse
    {
        $offlineProcessor = new OfflinePaymentProcessor();
        $offlineProcessor->createOrder($payment->user, $payment);

        return redirect()->route('payments.offline-instructions', ['payment' => $payment->payment_id])
            ->with('success', __('payment.offline_order_created'));
    }
}