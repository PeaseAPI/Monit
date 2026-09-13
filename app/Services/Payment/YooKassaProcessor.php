<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * YooKassa 支付处理器（规格书 §11）
 *
 * 金额/币种统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单与 RUB 硬编码（#8）。
 */
class YooKassaProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'yookassa', $frequency);

        return [
            'processor' => 'yookassa',
            'shop_id' => config('services.yookassa.shop_id'),
            'secret_key' => config('services.yookassa.secret_key'),
            'amount' => [
                'value' => number_format((float) $ctx['amount'], 2, '.', ''),
                'currency' => $ctx['currency'],
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => route('pay.thank_you'),
            ],
            'description' => $plan->name.' 订阅',
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

