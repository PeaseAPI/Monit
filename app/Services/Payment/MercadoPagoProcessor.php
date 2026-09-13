<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * MercadoPago 支付处理器（规格书 §11）
 *
 * 金额/币种统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单与 BRL 硬编码（#8）。
 */
class MercadoPagoProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'mercadopago', $frequency);

        return [
            'processor' => 'mercadopago',
            'access_token' => config('services.mercadopago.access_token'),
            'item_title' => $plan->name,
            'item_quantity' => 1,
            'item_unit_price' => $ctx['amount'],
            'currency_id' => $ctx['currency'],
            'external_reference' => $ctx['order_ref'].'|'.json_encode([
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ]),
        ];
    }
}

