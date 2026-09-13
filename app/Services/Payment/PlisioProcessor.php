<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * Plisio 加密货币支付处理器（规格书 §11）
 *
 * 金额/币种/单号统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单（#8）。
 * order_number 即订单 external_id，webhook（/webhooks/plisio）按其匹配入账。
 */
class PlisioProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'plisio', $frequency);

        return [
            'processor' => 'plisio',
            'api_key' => config('services.plisio.api_key'),
            'order_number' => $ctx['order_ref'],
            'order_name' => $plan->name,
            'source_currency' => $ctx['currency'],
            'source_amount' => $ctx['amount'],
            'callback_url' => url('/webhooks/plisio'),
            'success_url' => route('pay.thank_you'),
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

