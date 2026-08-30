<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Revolut 支付处理器（规格书 §11）
 */
class RevolutProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        return [
            'processor' => 'revolut',
            'public_id' => config('services.revolut.public_id'),
            'order_id' => 'monit-' . $user->user_id . '-' . time(),
            'amount' => (int) ($this->getPrice($plan, $frequency) * 100),
            'currency' => 'USD',
            'name' => $plan->name,
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $event = $request->input('event');
        if ($event !== 'ORDER_COMPLETED') {
            return null;
        }

        $order = $request->input('order', []);
        $metadata = $order['metadata'] ?? [];

        $user = User::find($metadata['user_id'] ?? 0);
        $plan = Plan::find($metadata['plan_id'] ?? 0);

        if (!$user || !$plan) {
            return null;
        }

        $totalAmount = ($order['total_amount'] ?? 0) / 100;

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'revolut',
            'payment_id_external' => $order['id'] ?? null,
            'payment_frequency' => $metadata['frequency'] ?? 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $totalAmount,
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $totalAmount,
            'currency' => $order['currency'] ?? 'USD',
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
