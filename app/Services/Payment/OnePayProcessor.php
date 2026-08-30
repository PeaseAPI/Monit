<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * OnePay 支付处理器（规格书 §11：1pay.ch）
 */
class OnePayProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        return [
            'processor' => 'onepay',
            'merchant_code' => config('services.onepay.merchant_code'),
            'order_id' => 'monit-' . $user->user_id . '-' . time(),
            'amount' => $this->getPrice($plan, $frequency),
            'currency' => 'USD',
            'description' => $plan->name . ' 订阅',
            'return_url' => route('pay.thank_you'),
            'callback_url' => url('/webhooks/onepay'),
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $status = $request->input('status');
        if ($status !== 'success') {
            return null;
        }

        $metadata = json_decode($request->input('metadata', '{}'), true);

        $user = User::find($metadata['user_id'] ?? 0);
        $plan = Plan::find($metadata['plan_id'] ?? 0);

        if (!$user || !$plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'onepay',
            'payment_id_external' => $request->input('transaction_id'),
            'payment_frequency' => $metadata['frequency'] ?? 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $request->input('amount', 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $request->input('amount', 0),
            'currency' => $request->input('currency', 'USD'),
            'email' => $user->email,
            'name' => $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['USD'] ?? $plan->prices;
        return match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
