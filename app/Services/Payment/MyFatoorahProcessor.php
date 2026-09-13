<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\User;

/**
 * MyFatoorah 支付处理器（规格书 §11）
 *
 * 金额/币种统一来自 PaymentService::checkoutContext()（createOrder 创建的
 * pending 订单），修复旧实现坏回退导致的 0 元订单（#8）。
 */
class MyFatoorahProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $ctx = PaymentService::checkoutContext($user, $plan, 'myfatoorah', $frequency);
        $isTest = (bool) config('services.myfatoorah.is_test', true);
        $baseUrl = $isTest ? 'https://apitest.myfatoorah.com' : 'https://api.myfatoorah.com';

        return [
            'processor' => 'myfatoorah',
            'base_url' => $baseUrl,
            'invoice_value' => $ctx['amount'],
            'currency' => $ctx['currency'],
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'notification_option' => 'ALL',
            'language' => 'zh',
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }
}

