<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * OnePay 支付处理器（规格书 §11：1pay.ch）
 *
 * 金额/币种/单号统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单（#8）。
 * order_id 即订单 external_id，webhook（/webhooks/onepay）按其匹配入账。
 */
class OnePayProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'onepay', $frequency);

        return [
            'processor' => 'onepay',
            'merchant_code' => config('services.onepay.merchant_code'),
            'order_id' => $ctx['order_ref'],
            'amount' => $ctx['amount'],
            'currency' => $ctx['currency'],
            'description' => $plan->name.' 订阅',
            'return_url' => route('pay.thank_you'),
            'callback_url' => url('/webhooks/onepay'),
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

