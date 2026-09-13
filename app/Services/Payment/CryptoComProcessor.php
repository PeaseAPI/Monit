<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * Crypto.com 支付处理器（规格书 §11）
 *
 * 金额/币种/单号统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单（#8）。
 * order_id 即订单 external_id，webhook（/webhooks/crypto）按其匹配入账。
 */
class CryptoComProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'cryptocom', $frequency);

        return [
            'processor' => 'cryptocom',
            'merchant_id' => config('services.cryptocom.merchant_id'),
            'order_id' => $ctx['order_ref'],
            'amount' => $ctx['amount'],
            'currency' => $ctx['currency'],
            'description' => $plan->name,
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

