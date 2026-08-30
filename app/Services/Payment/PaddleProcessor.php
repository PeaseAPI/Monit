<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Paddle 支付处理器（规格书 §11）
 */
class PaddleProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $paddleConfig = config('services.paddle');

        return [
            'processor' => 'paddle',
            'vendor_id' => $paddleConfig['vendor_id'],
            'product_id' => $plan->settings['paddle_product_id'] ?? null,
            'custom_message' => $plan->name,
            'passthrough' => json_encode([
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ]),
        ];
    }

    public function handleWebhook(Request $request): Payment
    {
        $passthrough = json_decode($request->input('passthrough', '{}'), true);
        $user = User::findOrFail($passthrough['user_id'] ?? 0);
        $plan = Plan::findOrFail($passthrough['plan_id'] ?? 0);

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'paddle',
            'payment_id_external' => $request->input('order_id'),
            'payment_frequency' => $passthrough['frequency'] ?? 'one_time',
            'payment_type' => $request->input('alert_name') === 'subscription_created' ? 'recurring' : 'one_time',
            'base_amount' => $request->input('sale_gross', 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $request->input('sale_gross', 0),
            'currency' => $request->input('currency', 'USD'),
            'email' => $request->input('email', $user->email),
            'name' => $request->input('customer_name', $user->name),
            'datetime' => now(),
        ]);
    }
}
