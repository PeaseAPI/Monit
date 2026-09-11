<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * Crypto.com 支付处理器（规格书 §11）
 */
class CryptoComProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        return [
            'processor' => 'cryptocom',
            'merchant_id' => config('services.cryptocom.merchant_id'),
            'order_id' => 'monit-'.$user->user_id.'-'.time(),
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

        $data = Typed::arr($request->input('data'));
        $metadata = Typed::arr($data['metadata'] ?? []);

        $user = User::query()->where('user_id', Typed::int($metadata['user_id'] ?? 0))->first();
        $plan = Plan::query()->where('plan_id', Typed::int($metadata['plan_id'] ?? 0))->first();

        if (! $user || ! $plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'cryptocom',
            'payment_id_external' => Typed::stringOrNull($data['payment_id'] ?? null),
            'payment_frequency' => Typed::string($metadata['frequency'] ?? 'one_time'),
            'payment_type' => 'one_time',
            'base_amount' => Typed::float($data['amount'] ?? 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => Typed::float($data['amount'] ?? 0),
            'currency' => Typed::string($data['currency'] ?? 'USD'),
            'email' => $user->email,
            'name' => $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['USD'] ?? $plan->prices;

        return (float) match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
