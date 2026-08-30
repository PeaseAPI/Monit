<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 支付服务 - 统一处理支付下单/回调/订阅
 * 规格书 §11：Stripe + PayPal + Offline 三种处理器 MVP
 */
class PaymentService
{
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
            $codeModel = \App\Models\Code::where('code', $code)->first();

            if ($codeModel && $codeModel->type === 'discount' && ! $codeModel->redemptionIssue($user)) {
                $discountAmount = $amount * ((float) $codeModel->discount / 100);
                $codeId = $codeModel->code_id;
            }
        }

        $totalAmount = max(0, $amount - $discountAmount);

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

        $payment->update([
            'external_id' => $externalId,
            'status' => 1, // paid
            'last_datetime' => now(),
        ]);

        $user = $payment->user;

        // 更新用户支付信息
        $user->update([
            'payment_subscription_id' => $subscriptionId,
            'payment_processor' => $payment->payment_processor,
            'payment_total_amount' => ($user->payment_total_amount ?? 0) + $payment->total_amount,
            'payment_currency' => $payment->currency,
        ]);

        // 激活套餐
        $this->activatePlan($user, $payment);

        // 平台 Webhook 派发（规格 §6.3.1：webhooks.webhook_payment_success_url）
        app(\App\Services\WebhookService::class)->paymentSuccess([
            'payment_id' => $payment->payment_id,
            'user_id' => $user->user_id,
            'email' => $user->email,
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
        app(\App\Services\WebhookService::class)->paymentFailure([
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
        $codeModel = \App\Models\Code::where('code', $code)->first();

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
     * 处理订阅取消（规格书 §11：Webhook 回调）
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
            $payment->update([
                'status' => 1,
                'last_datetime' => now(),
            ]);

            $user = $payment->user;
            if ($user) {
                $this->activatePlan($user, $payment);
            }
        }
    }
}