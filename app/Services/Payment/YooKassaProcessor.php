<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * YooKassa 支付处理器（规格书 §11）
 */
class YooKassaProcessor
{
    /**
     * @return array<string, mixed>
     */
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
            'description' => $plan->name.' 订阅',
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

        $object = Typed::arr($request->input('object'));
        $metadata = Typed::arr($object['metadata'] ?? []);

        $user = User::query()->where('user_id', Typed::int($metadata['user_id'] ?? 0))->first();
        $plan = Plan::query()->where('plan_id', Typed::int($metadata['plan_id'] ?? 0))->first();

        if ($user === null || $plan === null) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'yookassa',
            'payment_id_external' => Typed::stringOrNull($object['id'] ?? null),
            'payment_frequency' => Typed::string($metadata['frequency'] ?? 'one_time'),
            'payment_type' => Typed::string(data_get($object, 'payment_method.type') ?? 'one_time'),
            'base_amount' => Typed::float(data_get($object, 'amount.value')),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => Typed::float(data_get($object, 'amount.value')),
            'currency' => Typed::string(data_get($object, 'amount.currency') ?? 'RUB'),
            'email' => $user->email,
            'name' => $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['RUB'] ?? $plan->prices['USD'] ?? $plan->prices;

        return (float) match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
