<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * iyzico 支付处理器（规格书 §11）
 *
 * 金额/币种/单号统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现 plans.prices 无 TRY/USD 直配价时回退整个 prices
 * 数组再取 monthly 键导致的 0.00 元订单与 TRY 币种硬编码问题（#8）。
 * conversation_id 即订单 external_id，webhook（/webhooks/iyzico）按其匹配入账。
 */
class IyzicoProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'iyzico', $frequency);

        return [
            'processor' => 'iyzico',
            'base_url' => config('services.iyzico.base_url', 'sandbox-api.iyzipay.com'),
            'conversation_id' => $ctx['order_ref'],
            'price' => $ctx['amount'],
            'paid_price' => $ctx['amount'],
            'currency' => $ctx['currency'],
            'basket_id' => 'B'.time(),
            'buyer' => [
                'id' => (string) $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'basket_items' => [[
                'id' => $plan->plan_id,
                'name' => $plan->name,
                'category1' => 'SaaS',
                'category2' => 'Analytics',
                'itemType' => 'VIRTUAL',
                'price' => $ctx['amount'],
            ]],
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

