<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Crypto.com 支付处理器（规格书 §11）
 */
class CryptoComProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        return [
            'processor' => 'cryptocom',
            'merchant_id' => config('services.cryptocom.merchant_id'),
            'order_id' => 'monit-' . $user->user_id . '-' . time(),
            'amount' => $this->getPrice($plan, $frequency),
            'currency' => 'USD',
            'description' => $plan->name,
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $event = $request->input('type');
        if ($event !== 'payment.completed') {
            return null;
        }

        $data = $request->input('data', []);
        $metadata = $data['metadata'] ?? [];

        $user = User::find($metadata['user_id'] ?? 0);
        $plan = Plan::find($metadata['plan_id'] ?? 0);

        if (!$user || !$plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'cryptocom',
            'payment_id_external' => $data['payment_id'] ?? null,
            'payment_frequency' => $metadata['frequency'] ?? 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $data['amount'] ?? 0,
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
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
