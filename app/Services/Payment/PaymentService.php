<?php

namespace App\Services\Payment;

use App\Models\Code;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\WebhookService;
use App\Support\Currency;
use Illuminate\Support\Facades\Log;

/**
 * 支付服务 - 统一处理支付下单/回调/订阅
 * 规格书 §11：Stripe + PayPal + Offline 三种处理器 MVP
 */
class PaymentService
{
    /**
     * ISO 4217 零小数币种（最小单位 == 主单位，不除 100）
     */
    public const ZERO_DECIMAL_CURRENCIES = [
        'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'UGX', 'KMF', 'XAF', 'XOF', 'XPF',
        'DJF', 'GNF', 'PYG', 'RWF', 'VUV',
    ];

    /**
     * 网关最小单位金额 → 主单位（ISO 4217 零小数币种表）
     * 非数值/缺失返回 null（调用方比对必败 → fail-closed）
     */
    public static function majorUnits(int|string|null $minorAmount, string $currency): ?float
    {
        if ($minorAmount === null || ! is_numeric($minorAmount)) {
            return null;
        }

        $factor = in_array(strtoupper(trim($currency)), self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100;

        return round(((float) $minorAmount) / $factor, 2);
    }

    /**
     * 网关金额/币种防篡改校验：验签只证明「通知来自网关」，
     * 不证明「结算金额 == 本地订单金额」。金额缺失/币种不匹配/超容差 → false
     */
    public function assertAmountMatches(Payment $payment, ?float $gatewayAmount, ?string $gatewayCurrency = null): bool
    {
        if ($gatewayAmount === null || $gatewayAmount < 0) {
            return false;
        }

        if ($gatewayCurrency !== null
            && strcasecmp(trim($gatewayCurrency), (string) $payment->currency) !== 0) {
            return false;
        }

        return abs($gatewayAmount - (float) $payment->total_amount) < 0.005;
    }

    /**
     * 网关回调入账前统一校验入口：按 payment_id 取本地订单，
     * 校验金额/币种；不通过记 warning（含两侧金额币种，便于对账排查）并返回 false
     */
    public function verifyGatewayAmount(int $paymentId, ?float $gatewayAmount, ?string $gatewayCurrency, string $gateway): bool
    {
        $payment = Payment::find($paymentId);

        if (! $payment || ! $this->assertAmountMatches($payment, $gatewayAmount, $gatewayCurrency)) {
            Log::warning('webhook.amount_mismatch', [
                'gateway' => $gateway,
                'payment_id' => $paymentId,
                'gateway_amount' => $gatewayAmount,
                'gateway_currency' => $gatewayCurrency,
                'local_amount' => $payment?->total_amount,
                'local_currency' => $payment?->currency,
            ]);

            return false;
        }

        return true;
    }

    /**
     * 创建支付订单
     */
    public function createOrder(User $user, Plan $plan, string $processor, string $frequency = 'one_time', ?string $code = null): array
    {
        $prices = $plan->prices ?? [];
        // 货币：用户历史支付货币，否则后台默认货币（默认 CNY，规格书 §10.4）
        $currency = Currency::normalize($user->payment_currency ?? '');

        // 获取定价：优先直配价，无则按默认货币价 × 汇率换算；无价不得下单（修复 0 元订单）
        $amount = Currency::planPrice($prices, $currency, $frequency);

        if ($amount === null) {
            throw new \RuntimeException("plan_price_missing:{$plan->plan_id}:{$frequency}");
        }

        // 计算折扣（完整走 redemptionIssue 校验：启用/有效期/次数/单用户一次，
        // 与结账页兑换口径一致，防止过期或超兑折扣码绕过）
        $discountAmount = 0;
        $codeId = null;
        if ($code) {
            $codeModel = Code::where('code', $code)->first();

            if ($codeModel && $codeModel->type === 'discount' && ! $codeModel->redemptionIssue($user)) {
                $discountAmount = $amount * ((float) $codeModel->discount / 100);
                $codeId = $codeModel->code_id;
            }
        }

        $totalAmount = max(0, $amount - $discountAmount);

        // 税费（payment.taxes_enabled，默认开启）：按后台税则与用户账单国家计算
        // value_type=percentage 按比例 / fixed 固定额；countries 空 = 全球适用
        $taxesAmount = 0;

        if (\App\Http\Controllers\PaymentController::taxesEnabled() && $billingCountry = strtoupper(trim((string) ($user->billing['country'] ?? '')))) {
            foreach (\App\Models\Tax::all() as $tax) {
                $countries = array_map(
                    fn ($c) => strtoupper(trim((string) $c)),
                    (array) ($tax->countries ?? [])
                );

                if (! empty($countries) && ! in_array($billingCountry, $countries, true)) {
                    continue;
                }

                $taxesAmount += $tax->value_type === 'percentage'
                    ? $totalAmount * ((float) $tax->value / 100)
                    : (float) $tax->value;
            }

            $taxesAmount = round($taxesAmount, 2);
        }

        $totalAmount = round($totalAmount + $taxesAmount, 2);

        // 创建支付记录（plan_id 固化本次购买套餐，激活/发票均以此为准）
        $payment = Payment::create([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'external_id' => null,
            'plan_id' => $plan->plan_id,
            'payment_processor' => $processor,
            'type' => $frequency === 'monthly' || $frequency === 'annual' ? 'recurring' : 'one_time',
            'frequency' => $frequency,
            'billing' => $user->billing,
            'status' => 0, // pending
            'code_id' => $codeId,
            'discount_amount' => $discountAmount,
            'taxes_amount' => $taxesAmount,
            'total_amount' => $totalAmount,
            'currency' => $currency,
            'datetime' => now(),
        ]);

        return [
            'payment_id' => $payment->payment_id,
            'amount' => $totalAmount,
            'currency' => $currency,
            'processor' => $processor,
            'frequency' => $frequency,
        ];
    }

    /**
     * 支付成功回调处理（幂等：重复回调不重复累计/续期/派发）
     */
    public function handlePaymentSuccess(int $paymentId, string $externalId, ?string $subscriptionId = null): Payment
    {
        $payment = Payment::findOrFail($paymentId);

        // 幂等守卫：已入账订单直接返回，防止网关重复通知导致
        // payment_total_amount 重复累计、套餐重复续期、webhook 重复派发
        if ($payment->status === 1) {
            return $payment;
        }

        return $this->settlePayment($payment, $externalId, $subscriptionId);
    }

    /**
     * 入账收尾（handlePaymentSuccess / handleExternalPaymentNotification 共用）：
     * 置 paid + 累计用户支付总额 + 激活套餐 + 派发平台 webhook。
     * 调用方负责幂等守卫（status===1 跳过）
     */
    private function settlePayment(Payment $payment, string $externalId, ?string $subscriptionId = null): Payment
    {
        $payment->update([
            'external_id' => $externalId,
            'status' => 1, // paid
            'last_datetime' => now(),
        ]);

        $user = $payment->user;

        if ($user) {
            // 更新用户支付信息（记账口径与直接入账路径一致）
            $user->update([
                'payment_subscription_id' => $subscriptionId,
                'payment_processor' => $payment->payment_processor,
                'payment_total_amount' => ($user->payment_total_amount ?? 0) + $payment->total_amount,
                'payment_currency' => $payment->currency,
            ]);

            // 激活套餐
            $this->activatePlan($user, $payment);
        }

        // 平台 Webhook 派发（规格 §6.3.1：webhooks.webhook_payment_success_url）
        app(WebhookService::class)->paymentSuccess([
            'payment_id' => $payment->payment_id,
            'user_id' => $payment->user_id,
            'email' => $payment->email,
            'plan_id' => $payment->plan_id,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency,
            'processor' => $payment->payment_processor,
        ]);

        return $payment;
    }

    /**
     * 支付失败回调处理（规格 §6.3.1：webhooks.webhook_payment_failure_url）
     * status：0=pending 1=paid 2=failed；幂等：已支付订单不可置为失败
     */
    public function handlePaymentFailure(int $paymentId, string $externalId = '', string $reason = ''): ?Payment
    {
        $payment = Payment::find($paymentId);

        if (! $payment || $payment->status === 1) {
            return $payment;
        }

        $payment->update(array_filter([
            'external_id' => $externalId ?: null,
            'status' => 2, // failed
            'last_datetime' => now(),
        ], fn ($value) => $value !== null));

        // 平台 Webhook 派发（规格 §6.3.1：webhooks.webhook_payment_failure_url）
        app(WebhookService::class)->paymentFailure([
            'payment_id' => $payment->payment_id,
            'user_id' => $payment->user_id,
            'email' => $payment->email,
            'amount' => $payment->total_amount,
            'currency' => $payment->currency,
            'processor' => $payment->payment_processor,
            'reason' => $reason,
        ]);

        return $payment;
    }

    /**
     * 激活套餐
     * 套餐来源优先级：payment->plan_id（本次购买快照）> user->plan_id（历史订单兜底）
     */
    public function activatePlan(User $user, Payment $payment): void
    {
        $plan = Plan::find($payment->plan_id ?: $user->plan_id);
        if (! $plan) {
            return;
        }

        // 计算过期时间
        $expirationDate = match ($payment->frequency) {
            'monthly' => now()->addMonth(),
            'annual' => now()->addYear(),
            'lifetime' => null, // 永不过期
            default => now()->addMonth(),
        };

        $user->update([
            'plan_id' => $plan->plan_id,
            'plan_expiration_date' => $expirationDate,
            'plan_settings' => $plan->settings,
        ]);
    }

    /**
     * 兑换码处理（统一走 Code::redemptionIssue/recordRedemption，规格 §10.3）
     */
    public function redeemCode(User $user, string $code): array
    {
        $codeModel = Code::where('code', $code)->first();

        if (! $codeModel) {
            return ['success' => false, 'message' => __('msg.code_not_found')];
        }

        if ($issue = $codeModel->redemptionIssue($user)) {
            // 映射到 msg.* 语言键（payments/redeem-code 端点约定）
            $key = str_replace('account.', 'msg.', $issue);

            return ['success' => false, 'message' => __($key)];
        }

        // 并发窗口内计数被打满时（recordRedemption 事务内重检）拒绝兑换
        if (! $codeModel->recordRedemption($user)) {
            return ['success' => false, 'message' => __('msg.code_fully_redeemed')];
        }

        $codeModel->applyToUser($user);

        return ['success' => true, 'message' => __('msg.code_redeemed')];
    }

    /**
     * 用户侧取消订阅（规格书 §6.2.6 /pay-billing/cancel）
     * 立即解除本地订阅标记；远端网关订阅由各处理器 Webhook 回调与到期续费流程自然终止。
     */
    public function cancelSubscription(User $user, string $processor): void
    {
        if ($user->payment_subscription_id && $user->payment_processor === $processor) {
            $user->update([
                'payment_subscription_id' => null,
                'payment_processor' => null,
            ]);
        }
    }

    /**
     * 处理订阅取消（Webhook 回调，规格书 §11）
     */
    public function handleSubscriptionCancelled(string $subscriptionId, string $processor): void
    {
        $user = User::where('payment_subscription_id', $subscriptionId)
            ->where('payment_processor', $processor)
            ->first();

        if ($user) {
            $user->update([
                'payment_subscription_id' => null,
                'payment_processor' => null,
            ]);
        }
    }

    /**
     * 处理外部支付通知（通过外部 ID 查找支付记录，规格书 §11）
     * 用于无法直接获取内部 payment_id 的 Webhook
     */
    public function handleExternalPaymentNotification(string $processor, string $externalId): void
    {
        $payment = Payment::where('payment_processor', $processor)
            ->where('external_id', $externalId)
            ->first();

        if ($payment && $payment->status !== 1) {
            // 记账口径与直接入账路径统一（周期 #14：此前不累计
            // payment_total_amount、不派发平台 webhook）
            $this->settlePayment($payment, $externalId);
        }
    }
}
