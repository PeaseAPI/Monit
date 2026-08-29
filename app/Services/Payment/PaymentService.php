<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
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
        $currency = $user->payment_currency ?? 'USD';

        // 获取定价
        $amount = match ($frequency) {
            'monthly' => $prices[$currency]['monthly'] ?? 0,
            'annual' => $prices[$currency]['annual'] ?? 0,
            'lifetime' => $prices[$currency]['lifetime'] ?? 0,
            default => $prices[$currency]['monthly'] ?? 0,
        };

        // 计算折扣
        $discountAmount = 0;
        $codeId = null;
        if ($code) {
            $codeModel = \App\Models\Code::where('code', $code)
                ->where('is_enabled', true)
                ->first();

            if ($codeModel && $codeModel->type === 'discount') {
                $discountAmount = $amount * ($codeModel->discount / 100);
                $codeId = $codeModel->code_id;
            }
        }

        $totalAmount = max(0, $amount - $discountAmount);

        // 创建支付记录
        $payment = Payment::create([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'external_id' => null,
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
     * 支付成功回调处理
     */
    public function handlePaymentSuccess(int $paymentId, string $externalId, ?string $subscriptionId = null): Payment
    {
        $payment = Payment::findOrFail($paymentId);

        $payment->update([
            'external_id' => $externalId,
            'status' => 1, // paid
            'last_datetime' => now(),
        ]);

        $user = $payment->user;
        $plan = Plan::find($user->plan_id);

        // 更新用户支付信息
        $user->update([
            'payment_subscription_id' => $subscriptionId,
            'payment_processor' => $payment->payment_processor,
            'payment_total_amount' => ($user->payment_total_amount ?? 0) + $payment->total_amount,
            'payment_currency' => $payment->currency,
        ]);

        // 激活套餐
        $this->activatePlan($user, $payment);

        return $payment;
    }

    /**
     * 激活套餐
     */
    public function activatePlan(User $user, Payment $payment): void
    {
        $plan = Plan::find($user->plan_id);
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
     * 兑换码处理
     */
    public function redeemCode(User $user, string $code): array
    {
        $codeModel = \App\Models\Code::where('code', $code)
            ->where('is_enabled', true)
            ->first();

        if (! $codeModel) {
            return ['success' => false, 'message' => __('msg.code_not_found')];
        }

        // 检查是否已兑换
        $alreadyRedeemed = \App\Models\RedeemedCode::where('user_id', $user->user_id)
            ->where('code_id', $codeModel->code_id)
            ->exists();

        if ($alreadyRedeemed) {
            return ['success' => false, 'message' => __('msg.code_already_redeemed')];
        }

        // 检查最大兑换次数
        if ($codeModel->max_redemptions > 0) {
            $totalRedemptions = \App\Models\RedeemedCode::where('code_id', $codeModel->code_id)->count();
            if ($totalRedemptions >= $codeModel->max_redemptions) {
                return ['success' => false, 'message' => __('msg.code_max_reached')];
            }
        }

        // 检查有效期
        if ($codeModel->date_end && now()->isAfter($codeModel->date_end)) {
            return ['success' => false, 'message' => __('msg.code_expired')];
        }

        // 兑换
        \App\Models\RedeemedCode::create([
            'user_id' => $user->user_id,
            'code_id' => $codeModel->code_id,
            'datetime' => now(),
        ]);

        // 如果是兑换码，增加套餐时长
        if ($codeModel->type === 'redeemable' && $codeModel->days) {
            $currentExpiry = $user->plan_expiration_date;
            $baseDate = $currentExpiry && $currentExpiry->isFuture() ? $currentExpiry : now();
            $user->update([
                'plan_expiration_date' => $baseDate->addDays($codeModel->days),
            ]);
        }

        return ['success' => true, 'message' => __('msg.code_redeemed')];
    }
}