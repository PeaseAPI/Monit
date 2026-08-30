<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Flutterwave 支付处理器（规格书 §11）
 */
class FlutterwaveProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $publicKey = config('services.flutterwave.public_key');

        return [
            'processor' => 'flutterwave',
            'public_key' => $publicKey,
            'tx_ref' => 'monit-' . $user->user_id . '-' . time(),
            'amount' => $this->getPrice($plan, $frequency),
            'currency' => 'USD',
            'payment_options' => 'card,banktransfer,ussd',
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'meta' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
            'customizations' => [
                'title' => config('app.name'),
                'description' => $plan->name . ' 订阅',
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $event = $request->input('event');
        if ($event !== 'charge.completed') {
            return null;
        }

        $data = $request->input('data', []);
        $meta = $data['meta'] ?? [];

        $user = User::find($meta['user_id'] ?? 0);
        $plan = Plan::find($meta['plan_id'] ?? 0);

        if (!$user || !$plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'flutterwave',
            'payment_id_external' => $data['id'] ?? null,
            'payment_frequency' => $meta['frequency'] ?? 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $data['amount'] ?? 0,
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'email' => $data['customer']['email'] ?? $user->email,
            'name' => $data['customer']['name'] ?? $user->name,
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
