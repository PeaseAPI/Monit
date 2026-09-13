<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;

/**
 * Paddle 支付处理器（规格书 §11）
 *
 * 金额由 Paddle 侧 product 决定，但金额/币种/单号仍统一走
 * PaymentService::checkoutContext()：校验价格存在（fail-closed）并创建
 * pending 订单，external_id 供 webhook（/webhooks/paddle）匹配入账（#8）。
 */
class PaddleProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'paddle', $frequency);
        $paddleConfig = Typed::arr(config('services.paddle'));

        return [
            'processor' => 'paddle',
            'vendor_id' => Typed::string($paddleConfig['vendor_id'] ?? ''),
            'product_id' => $plan->settings['paddle_product_id'] ?? null,
            'custom_message' => $plan->name,
            'passthrough' => json_encode([
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
                'order_ref' => $ctx['order_ref'],
            ]),
        ];
    }
}

