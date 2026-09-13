<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * PayU 支付处理器（规格书 §11）
 *
 * 金额/币种/单号统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单与 PLN 硬编码（#8）。
 * ext_order_id 即订单 external_id，webhook（/webhooks/payu）按其匹配入账。
 */
class PayUProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'payu', $frequency);
        $amountMinor = (int) round(((float) $ctx['amount']) * 100);

        return [
            'processor' => 'payu',
            'pos_id' => config('services.payu.pos_id'),
            'ext_order_id' => $ctx['order_ref'],
            'customer_ip' => request()->ip(),
            'merchant_pos_id' => config('services.payu.pos_id'),
            'description' => $plan->name.' 订阅',
            'currency_code' => $ctx['currency'],
            'total_amount' => $amountMinor,
            'buyer' => [
                'email' => $user->email,
                'firstName' => $user->name,
            ],
            'products' => [[
                'name' => $plan->name,
                'unitPrice' => $amountMinor,
                'quantity' => 1,
            ]],
            'notify_url' => url('/webhooks/payu'),
            'continue_url' => route('pay.thank_you'),
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

