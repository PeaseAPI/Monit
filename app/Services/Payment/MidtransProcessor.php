<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * Midtrans 支付处理器（规格书 §11）
 *
 * 金额统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单（#8）。
 * order_id 即订单 external_id，webhook（/webhooks/midtrans）按其匹配入账。
 */
class MidtransProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'midtrans', $frequency);

        return [
            'processor' => 'midtrans',
            'client_key' => config('services.midtrans.client_key'),
            'server_key' => config('services.midtrans.server_key'),
            'transaction_details' => [
                'order_id' => $ctx['order_ref'],
                'gross_amount' => $ctx['amount'],
            ],
            'item_details' => [[
                'id' => $plan->plan_id,
                'price' => $ctx['amount'],
                'quantity' => 1,
                'name' => $plan->name,
            ]],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'custom_field1' => json_encode([
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ]),
        ];
    }
}

