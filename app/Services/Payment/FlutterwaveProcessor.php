<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * Flutterwave 支付处理器（规格书 §11）
 *
 * 金额/币种/单号统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单（#8）。
 * tx_ref 即订单 external_id，webhook（/webhooks/flutterwave）按其匹配入账。
 */
class FlutterwaveProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'flutterwave', $frequency);

        return [
            'processor' => 'flutterwave',
            'public_key' => config('services.flutterwave.public_key'),
            'tx_ref' => $ctx['order_ref'],
            'amount' => $ctx['amount'],
            'currency' => $ctx['currency'],
            'payment_options' => 'card,banktransfer,ussd',
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'meta' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
            'customizations' => [
                'title' => config('app.name'),
                'description' => $plan->name.' 订阅',
            ],
        ];
    }
}

