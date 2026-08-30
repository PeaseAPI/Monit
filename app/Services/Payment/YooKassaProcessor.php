<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * YooKassa 支付处理器（规格书 §11）
 */
class YooKassaProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $shopId = config('services.yookassa.shop_id');
        $secretKey = config('services.yookassa.secret_key');

        return [
            'processor' => 'yookassa',
            'shop_id' => $shopId,
            'amount' => [
                'value' => $this->getPrice($plan, $frequency),
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => route('pay.thank_you'),
            ],
            'description' => $plan->name . ' 订阅',
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
        if ($event !== 'payment.succeeded') {
            return null;
        }

        $object = $request->input('object', []);
        $metadata = $object['metadata'] ?? [];

        $user = User::find($metadata['user_id'] ?? 0);
        $plan = Plan::find($metadata['plan_id'] ?? 0);

        if (!$user || !$plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'yookassa',
            'payment_id_external' => $object['id'] ?? null,
            'payment_frequency' => $metadata['frequency'] ?? 'one_time',
            'payment_type' => $object['payment_method']['type'] ?? 'one_time',
            'base_amount' => $object['amount']['value'] ?? 0,
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $object['amount']['value'] ?? 0,
            'currency' => $object['amount']['currency'] ?? 'RUB',
            'email' => $user->email,
            'name' => $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['RUB'] ?? $plan->prices['USD'] ?? $plan->prices;
        return match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
