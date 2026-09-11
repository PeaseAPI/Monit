<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\AlipayProcessor;
use App\Services\Payment\CryptoComProcessor;
use App\Services\Payment\FlutterwaveProcessor;
use App\Services\Payment\IyzicoProcessor;
use App\Services\Payment\KlarnaProcessor;
use App\Services\Payment\LemonsqueezyProcessor;
use App\Services\Payment\MercadoPagoProcessor;
use App\Services\Payment\MidtransProcessor;
use App\Services\Payment\MollieProcessor;
use App\Services\Payment\MyFatoorahProcessor;
use App\Services\Payment\OfflinePaymentProcessor;
use App\Services\Payment\OnePayProcessor;
use App\Services\Payment\PaddleProcessor;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PayPalProcessor;
use App\Services\Payment\PaystackProcessor;
use App\Services\Payment\PayUProcessor;
use App\Services\Payment\PlisioProcessor;
use App\Services\Payment\RazorpayProcessor;
use App\Services\Payment\RevolutProcessor;
use App\Services\Payment\StripeProcessor;
use App\Services\Payment\WeChatPayProcessor;
use App\Services\Payment\YooKassaProcessor;
use App\Support\Settings;
use App\Support\Typed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 用户支付流程控制器
 * 规格书 §10/§11：套餐购买 / 支付 / 订阅
 */
class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    /**
     * 全部 22 个支付处理器（规格书 §11）
     * 6 个专线处理器 + wechat/alipay（createOrder 型）+ 14 个通用托管结算型（createCheckout 型）
     */
    public const PROCESSORS = [
        'stripe', 'paypal', 'razorpay', 'mollie', 'paystack', 'offline',
        'wechat', 'alipay',
        'payu', 'iyzico', 'yookassa', 'cryptocom', 'paddle', 'mercadopago',
        'midtrans', 'flutterwave', 'lemonsqueezy', 'myfatoorah', 'klarna',
        'plisio', 'revolut', 'onepay',
    ];

    /** 14 个 createCheckout(User, Plan, frequency) 型处理器 */
    protected const GENERIC_PROCESSORS = [
        'payu' => PayUProcessor::class,
        'iyzico' => IyzicoProcessor::class,
        'yookassa' => YooKassaProcessor::class,
        'cryptocom' => CryptoComProcessor::class,
        'paddle' => PaddleProcessor::class,
        'mercadopago' => MercadoPagoProcessor::class,
        'midtrans' => MidtransProcessor::class,
        'flutterwave' => FlutterwaveProcessor::class,
        'lemonsqueezy' => LemonsqueezyProcessor::class,
        'myfatoorah' => MyFatoorahProcessor::class,
        'klarna' => KlarnaProcessor::class,
        'plisio' => PlisioProcessor::class,
        'revolut' => RevolutProcessor::class,
        'onepay' => OnePayProcessor::class,
    ];

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * 支付页面 - 选择套餐和支付方式
     */
    public function index(Request $request): View
    {
        // 支付总开关（payment.payment_is_enabled，默认开启）：关闭时前台引导回仪表盘
        if (! self::paymentEnabled()) {
            abort(404);
        }

        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();
        $user = $this->user();
        $currentPlan = Plan::find($user->plan_id);
        $recentPayments = $user->payments()->orderByDesc('datetime')->limit(5)->get();

        // 已启用的支付处理器（后台开关/凭据决定；前台仅展示这些，默认选中第一个）
        $enabledProcessors = self::enabledProcessors();

        // 结账页默认周期（payment.default_payment_frequency：monthly/annual/lifetime）
        $defaultFrequency = in_array(
            trim(Typed::string(Settings::get('payment.default_payment_frequency', ''))),
            ['monthly', 'annual', 'lifetime'],
            true
        ) ? trim(Typed::string(Settings::get('payment.default_payment_frequency'))) : 'monthly';

        return view('payments.index', compact('plans', 'user', 'currentPlan', 'recentPayments', 'defaultFrequency', 'enabledProcessors'));
    }

    /**
     * 发起支付（规格书 §11：22 处理器统一入口）
     *
     * @return View|RedirectResponse|Response
     */
    public function checkout(Request $request)
    {
        if (! self::paymentEnabled()) {
            abort(404);
        }

        $validated = Typed::arr($request->validate([
            'plan_id' => ['required', 'string', 'exists:plans,plan_id'],
            'processor' => ['required', 'string', 'in:'.implode(',', self::PROCESSORS)],
            'frequency' => ['required', 'in:monthly,annual,lifetime'],
            'code' => ['nullable', 'string'],
        ]));

        $user = $this->user();
        $plan = Plan::query()->where('plan_id', Typed::int($validated['plan_id']))->firstOrFail();
        $processor = Typed::string($validated['processor']);
        $frequency = Typed::string($validated['frequency']);

        // 服务端强校验：仅允许后台已启用的支付方式（防绕过前端直接 POST）
        if (! in_array($processor, self::enabledProcessors(), true)) {
            return back()->withErrors(['processor' => __('msg.payment_processor_disabled')]);
        }

        try {
            $order = $this->paymentService->createOrder($user, $plan, $processor, $frequency, Typed::stringOrNull($validated['code'] ?? null));
        } catch (\RuntimeException $e) {
            // 套餐在该货币下无可用定价（规格书 §10.4：无价不得下单）
            if (str_starts_with($e->getMessage(), 'plan_price_missing')) {
                return back()->withErrors(['processor' => __('msg.plan_price_missing')]);
            }

            throw $e;
        }
        $payment = Payment::query()->where('payment_id', Typed::int($order['payment_id']))->firstOrFail();

        return match ($processor) {
            'stripe' => $this->redirectToStripe($payment),
            'paypal' => $this->redirectToPayPal($payment),
            'razorpay' => $this->redirectToRazorpay($payment),
            'mollie' => $this->redirectToMollie($payment),
            'paystack' => $this->redirectToPaystack($payment),
            'offline' => $this->handleOffline($payment),
            'wechat' => $this->redirectToWeChatPay($payment),
            'alipay' => $this->redirectToAlipay($payment),
            default => $this->redirectToGenericProcessor($user, $plan, $payment, $processor, $frequency),
        };
    }

    /**
     * 支付成功回调页面
     */
    public function success(Request $request): View
    {
        $paymentId = $request->query('payment_id');
        $payment = ((bool) $paymentId) ? Payment::find($paymentId) : null;

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
        $validated = Typed::arr($request->validate([
            'code' => ['required', 'string'],
        ]));

        $result = $this->paymentService->redeemCode($this->user(), Typed::string($validated['code']));

        if (! (bool) $result['success']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * 上传离线支付凭证
     * 归属校验（防 IDOR）：仅订单本人可上传，防止污染他人订单 billing 快照
     */
    public function uploadProof(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->user_id === $this->user()->user_id, 403);

        $offlineProcessor = new OfflinePaymentProcessor;
        $result = $offlineProcessor->uploadProof($request, $payment);

        if (! (bool) ($result['success'] ?? false)) {
            return back()->withErrors(['proof' => $result['message'] ?? __('payment.proof_upload_failed')]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Stripe Webhook
     *
     * @return JsonResponse
     */
    public function stripeWebhook(Request $request)
    {
        $stripeProcessor = new StripeProcessor;

        if (! $stripeProcessor->verifyWebhook($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $stripeProcessor->parseWebhookEvent($request);

        if ($event['event'] === 'payment_success' && isset($event['payment_id'])) {
            // 金额防篡改（安全审计周期 #19）：验签只证明通知来自 Stripe，
            // 结算金额仍须与本地订单一致（amount_total 最小单位 → 主单位换算）
            if ($this->paymentService->verifyGatewayAmount(
                Typed::int($event['payment_id']),
                PaymentService::majorUnits(Typed::intOrNull($event['amount_total'] ?? null), Typed::string($event['currency'] ?? '')),
                Typed::string($event['currency'] ?? ''),
                'stripe',
            )) {
                $this->paymentService->handlePaymentSuccess(
                    Typed::int($event['payment_id']),
                    Typed::string($event['external_id']),
                    Typed::stringOrNull($event['subscription_id'] ?? null)
                );
            }
        }

        if ($event['event'] === 'payment_failure' && isset($event['payment_id'])) {
            $this->paymentService->handlePaymentFailure(
                Typed::int($event['payment_id']),
                Typed::string($event['external_id'] ?? ''),
                Typed::string($event['reason'] ?? '')
            );
        }

        return response()->json(['received' => true]);
    }

    /**
     * PayPal Webhook
     *
     * @return JsonResponse
     */
    public function paypalWebhook(Request $request)
    {
        $payPalProcessor = new PayPalProcessor;

        if (! $payPalProcessor->verifyWebhook($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventType = $request->input('event_type', '');

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = Typed::arr($request->input('resource'));
            $paymentId = $resource['custom_id'] ?? null;
            $externalId = $resource['id'] ?? null;

            // 金额防篡改（安全审计周期 #19）：resource.amount.total 为主单位字符串，
            // 须与本地订单一致方可入账（缺失/不符 fail-closed 拒绝）
            if ((bool) $paymentId
                && $this->paymentService->verifyGatewayAmount(
                    Typed::int($paymentId),
                    is_numeric(data_get($resource, 'amount.total')) ? (float) data_get($resource, 'amount.total') : null,
                    Typed::string(data_get($resource, 'amount.currency') ?? ''),
                    'paypal',
                )) {
                $this->paymentService->handlePaymentSuccess(Typed::int($paymentId), Typed::string($externalId));
            }
        }

        // 规格 §6.3.1：支付失败事件派发 webhook_payment_failure_url
        if ($eventType === 'PAYMENT.CAPTURE.DENIED') {
            $resource = Typed::arr($request->input('resource'));
            $paymentId = $resource['custom_id'] ?? null;

            if ((bool) $paymentId) {
                $this->paymentService->handlePaymentFailure(
                    Typed::int($paymentId),
                    Typed::string($resource['id'] ?? ''),
                    Typed::string(data_get($resource, 'status_details.reason') ?? ''));
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * 用户支付历史
     */
    public function history(Request $request): View
    {
        $payments = $this->user()->payments()
            ->orderByDesc('datetime')
            ->paginate(20);

        return view('payments.history', compact('payments'));
    }

    /* -----------------------------------------------------------------
     | 私有方法
     ----------------------------------------------------------------- */

    protected function redirectToStripe(Payment $payment): View|RedirectResponse
    {
        $stripeProcessor = new StripeProcessor;

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

        return view('payments.processor-checkout', [
            'payment' => $payment,
            'processor' => 'stripe',
            'result' => $result,
        ]);
    }

    protected function redirectToPayPal(Payment $payment): RedirectResponse
    {
        $payPalProcessor = new PayPalProcessor;

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

        if ((bool) ($result['approve_url'] ?? null)) {
            return redirect(Typed::string($result['approve_url']));
        }

        return back()->withErrors(['processor' => __('payment.paypal_order_failed')]);
    }

    protected function handleOffline(Payment $payment): View
    {
        $paymentUser = $payment->user;

        if ($paymentUser === null) {
            abort(404);
        }

        $offlineProcessor = new OfflinePaymentProcessor;
        $result = $offlineProcessor->createOrder($paymentUser, $payment);

        return view('payments.offline-instructions', [
            'payment' => $payment,
            'instructions' => Typed::string($result['instructions'] ?? ''),
        ])->with('success', __('payment.offline_order_created'));
    }

    protected function redirectToRazorpay(Payment $payment): View|RedirectResponse
    {
        $razorpayProcessor = new RazorpayProcessor;
        if (! $razorpayProcessor->isConfigured()) {
            return back()->withErrors(['processor' => __('payment.razorpay_not_configured')]);
        }
        $result = $razorpayProcessor->createOrder($payment,
            route('payments.success', ['payment_id' => $payment->payment_id]),
            route('payments.cancel')
        );
        if (isset($result['error'])) {
            return back()->withErrors(['processor' => $result['error']]);
        }

        return view('payments.razorpay-checkout', compact('payment', 'result'));
    }

    protected function redirectToMollie(Payment $payment): RedirectResponse
    {
        $mollieProcessor = new MollieProcessor;
        if (! $mollieProcessor->isConfigured()) {
            return back()->withErrors(['processor' => __('payment.mollie_not_configured')]);
        }
        $result = $mollieProcessor->createOrder($payment,
            route('payments.success', ['payment_id' => $payment->payment_id]),
            route('payments.cancel')
        );
        if (isset($result['error'])) {
            return back()->withErrors(['processor' => $result['error']]);
        }
        if ((bool) ($result['checkout_url'] ?? null)) {
            return redirect(Typed::string($result['checkout_url']));
        }

        return back()->withErrors(['processor' => __('payment.mollie_order_failed')]);
    }

    protected function redirectToPaystack(Payment $payment): RedirectResponse
    {
        $paystackProcessor = new PaystackProcessor;
        if (! $paystackProcessor->isConfigured()) {
            return back()->withErrors(['processor' => __('payment.paystack_not_configured')]);
        }
        $result = $paystackProcessor->createOrder($payment,
            route('payments.success', ['payment_id' => $payment->payment_id]),
            route('payments.cancel')
        );
        if (isset($result['error'])) {
            return back()->withErrors(['processor' => $result['error']]);
        }
        if ((bool) ($result['authorization_url'] ?? null)) {
            return redirect(Typed::string($result['authorization_url']));
        }

        return back()->withErrors(['processor' => __('payment.paystack_order_failed')]);
    }

    /**
     * 微信 Native 支付：返回 code_url 供扫码（规格书 §11：WeChat Pay）
     */
    protected function redirectToWeChatPay(Payment $payment): View|RedirectResponse
    {
        $processor = new WeChatPayProcessor;

        if (! $processor->isConfigured()) {
            return back()->withErrors(['processor' => __('payment.wechat_not_configured')]);
        }

        $result = $processor->createOrder($payment,
            route('payments.success', ['payment_id' => $payment->payment_id]),
            route('payments.cancel')
        );

        if (isset($result['error'])) {
            return back()->withErrors(['processor' => $result['error']]);
        }

        return view('payments.wechat-pay', ['payment' => $payment, 'result' => $result]);
    }

    /**
     * 支付宝电脑网站支付：返回自动提交表单 HTML（规格书 §11：Alipay）
     */
    protected function redirectToAlipay(Payment $payment): Response|RedirectResponse
    {
        $processor = new AlipayProcessor;

        if (! $processor->isConfigured()) {
            return back()->withErrors(['processor' => __('payment.alipay_not_configured')]);
        }

        $result = $processor->createOrder($payment,
            route('payments.success', ['payment_id' => $payment->payment_id]),
            route('payments.cancel')
        );

        if (isset($result['error'])) {
            return back()->withErrors(['processor' => $result['error']]);
        }

        return response(Typed::string($result['redirect_html']));
    }

    /**
     * 通用托管结算型处理器（14 个，规格书 §11）
     * createCheckout 构造网关结算参数；生产环境由服务端向网关发起请求后跳转，
     * 此处渲染结算确认页（支付完成后由各自 Webhook 回调 finalize）。
     *
     * @param  User  $user
     */
    protected function redirectToGenericProcessor($user, Plan $plan, Payment $payment, string $processor, string $frequency): View|RedirectResponse
    {
        $class = self::GENERIC_PROCESSORS[$processor] ?? null;

        if ($class === null) {
            return back()->withErrors(['processor' => __('payment.unsupported_processor')]);
        }

        $instance = new $class;

        try {
            $result = $instance->createCheckout($user, $plan, $frequency);
        } catch (\Throwable $e) {
            return back()->withErrors(['processor' => $e->getMessage()]);
        }

        // 若处理器直接给出网关跳转 URL 则跳转
        foreach (['redirect_url', 'url', 'checkout_url', 'approve_url', 'authorization_url', 'payment_url', 'pay_url', 'hosted_checkout_url'] as $key) {
            if (is_string($result[$key] ?? null) && $result[$key] !== '') {
                return redirect()->away($result[$key]);
            }
        }

        return view('payments.processor-checkout', [
            'payment' => $payment,
            'processor' => $processor,
            'result' => $result,
        ]);
    }

    /**
     * 支付总开关（payment.payment_is_enabled，默认开启）
     */
    public static function paymentEnabled(): bool
    {
        $value = Settings::get('payment.payment_is_enabled');

        return $value === null || in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    /**
     * 后台已启用的支付处理器（前台仅可选择已启用的方式，默认选中第一个）
     * - 开关型（stripe/paypal/razorpay/offline/wechat/alipay）：settings payment.{key}_is_enabled
     * - 凭据型（其余 16 网关）：config/services.{gateway} 任一凭据非空即视为可用
     * 结果按 PROCESSORS 声明顺序返回，保证“第一个启用项”成为结账页默认选择。
     *
     * @return list<string>
     */
    public static function enabledProcessors(): array
    {
        $switches = [
            'stripe' => 'stripe_is_enabled',
            'paypal' => 'paypal_is_enabled',
            'razorpay' => 'razorpay_is_enabled',
            'offline' => 'offline_is_enabled',
            'wechat' => 'wechat_pay_is_enabled',
            'alipay' => 'alipay_is_enabled',
        ];

        $enabled = [];
        foreach (self::PROCESSORS as $processor) {
            if (isset($switches[$processor])) {
                if (filter_var(Settings::get('payment.'.$switches[$processor]), FILTER_VALIDATE_BOOLEAN)) {
                    $enabled[] = $processor;
                }

                continue;
            }

            foreach ((array) config('services.'.$processor) as $value) {
                if (is_string($value) && trim($value) !== '') {
                    $enabled[] = $processor;

                    break;
                }
            }
        }

        return $enabled;
    }

    /**
     * 税费开关（payment.taxes_enabled，默认开启）：结账时是否应用后台税则
     */
    public static function taxesEnabled(): bool
    {
        $value = Settings::get('payment.taxes_enabled');

        return $value === null || in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    /**
     * 发票开关（payment.invoice_is_enabled，默认开启）：支付历史页是否提供发票下载
     */
    public static function invoiceEnabled(): bool
    {
        $value = Settings::get('payment.invoice_is_enabled');

        return $value === null || in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
