<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * Revolut 支付处理器（规格书 §11）
 *
 * 金额/币种统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单与 USD 硬编码（#8）。
 * order_id 即订单 external_id，webhook（/webhooks/revolut）按其匹配入账。
 */
class RevolutProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'revolut', $frequency);

        return [
            'processor' => 'revolut',
            'public_id' => config('services.revolut.public_id'),
            'order_id' => $ctx['order_ref'],
            'amount' => (int) round(((float) $ctx['amount']) * 100),
            'currency' => $ctx['currency'],
            'name' => $plan->name,
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

