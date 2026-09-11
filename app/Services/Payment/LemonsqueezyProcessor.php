<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * Lemonsqueezy 支付处理器（规格书 §11）
 */
class LemonsqueezyProcessor
{
    /**
     * @return array<string, mixed>
     */
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

        $data = Typed::arr($request->input('data'));
        $customData = Typed::arr(data_get($data, 'attributes.custom_data'));

        $user = User::query()->where('user_id', Typed::int($customData['user_id'] ?? 0))->first();
        $plan = Plan::query()->where('plan_id', Typed::int($customData['plan_id'] ?? 0))->first();

        if (! $user || ! $plan) {
            return null;
        }

        $attrs = Typed::arr($data['attributes'] ?? []);

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'lemonsqueezy',
            'payment_id_external' => $data['id'] ?? null,
            'payment_frequency' => Typed::string($customData['frequency'] ?? 'one_time'),
            'payment_type' => $eventName === 'subscription_created' ? 'recurring' : 'one_time',
            'base_amount' => Typed::float($attrs['subtotal'] ?? 0) / 100,
            'discount_amount' => Typed::float($attrs['discount_total'] ?? 0) / 100,
            'taxes_amount' => Typed::float($attrs['tax'] ?? 0) / 100,
            'total_amount' => Typed::float($attrs['total'] ?? 0) / 100,
            'currency' => strtoupper(Typed::string($attrs['currency'] ?? 'USD')),
            'email' => Typed::stringOrNull($attrs['user_email'] ?? null) ?? $user->email,
            'name' => Typed::stringOrNull($attrs['user_name'] ?? null) ?? $user->name,
            'datetime' => now(),
        ]);
    }
}
