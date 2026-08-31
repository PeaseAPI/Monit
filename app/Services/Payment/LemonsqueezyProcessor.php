<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Lemonsqueezy 支付处理器（规格书 §11）
 */
class LemonsqueezyProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        return [
            'processor' => 'lemonsqueezy',
            'store_id' => config('services.lemonsqueezy.store_id'),
            'variant_id' => $plan->settings['lemonsqueezy_variant_id'] ?? null,
            'custom_data' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
            'checkout_data' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $eventName = $request->input('meta.event_name');
        if (! in_array($eventName, ['order_created', 'subscription_created'])) {
            return null;
        }

        $data = $request->input('data', []);
        $customData = $data['attributes']['custom_data'] ?? [];

        $user = User::find($customData['user_id'] ?? 0);
        $plan = Plan::find($customData['plan_id'] ?? 0);

        if (! $user || ! $plan) {
            return null;
        }

        $attrs = $data['attributes'] ?? [];

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'lemonsqueezy',
            'payment_id_external' => $data['id'] ?? null,
            'payment_frequency' => $customData['frequency'] ?? 'one_time',
            'payment_type' => $eventName === 'subscription_created' ? 'recurring' : 'one_time',
            'base_amount' => ($attrs['subtotal'] ?? 0) / 100,
            'discount_amount' => ($attrs['discount_total'] ?? 0) / 100,
            'taxes_amount' => ($attrs['tax'] ?? 0) / 100,
            'total_amount' => ($attrs['total'] ?? 0) / 100,
            'currency' => strtoupper($attrs['currency'] ?? 'USD'),
            'email' => $attrs['user_email'] ?? $user->email,
            'name' => $attrs['user_name'] ?? $user->name,
            'datetime' => now(),
        ]);
    }
}
