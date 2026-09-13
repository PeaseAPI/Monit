<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * Klarna 支付处理器（规格书 §11）
 *
 * 金额/币种统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单与 EUR/USD 硬编码（#8）。
 */
class KlarnaProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'klarna', $frequency);
        $region = (string) config('services.klarna.region', 'eu');
        $baseUrl = $region === 'us' ? 'https://api-na.klarna.com' : 'https://api.klarna.com';
        $amountMinor = (int) round(((float) $ctx['amount']) * 100);

        return [
            'processor' => 'klarna',
            'base_url' => $baseUrl,
            'purchase_country' => $region === 'us' ? 'US' : 'SE',
            'purchase_currency' => $ctx['currency'],
            'order_amount' => $amountMinor,
            'order_tax_amount' => 0,
            'order_lines' => [[
                'type' => 'digital',
                'name' => $plan->name,
                'quantity' => 1,
                'unit_price' => $amountMinor,
                'total_amount' => $amountMinor,
                'total_tax_amount' => 0,
            ]],
            'merchant_urls' => [
                'confirmation' => route('pay.thank_you'),
                'notification' => url('/webhooks/klarna'),
            ],
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

