<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * Lemonsqueezy 支付处理器（规格书 §11）
 *
 * 金额由 Lemonsqueezy 侧 variant 决定，但金额/币种/单号仍统一走
 * PaymentService::checkoutContext()：校验价格存在（fail-closed）并创建
 * pending 订单，external_id 供 webhook（/webhooks/lemonsqueezy）匹配入账（#8）。
 */
class LemonsqueezyProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'lemonsqueezy', $frequency);

        return [
            'processor' => 'lemonsqueezy',
            'store_id' => config('services.lemonsqueezy.store_id'),
            'variant_id' => $plan->settings['lemonsqueezy_variant_id'] ?? null,
            'custom_data' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
                'order_ref' => $ctx['order_ref'],
            ],
            'checkout_data' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
        ];
    }
}

