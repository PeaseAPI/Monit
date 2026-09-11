<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * MercadoPago 支付处理器（规格书 §11）
 */
class MercadoPagoProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $accessToken = config('services.mercadopago.access_token');

        return [
            'processor' => 'mercadopago',
            'access_token' => $accessToken,
            'item_title' => $plan->name,
            'item_quantity' => 1,
            'item_unit_price' => $this->getPrice($plan, $frequency),
            'currency_id' => 'BRL',
            'external_reference' => json_encode([
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ]),
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $data = $request->all();
        if (($data['type'] ?? '') !== 'payment') {
            return null;
        }

        $externalRef = Typed::arr(json_decode(Typed::string(data_get($data, 'data.external_reference') ?? '{}'), true));
        $user = User::query()->where('user_id', Typed::int($externalRef['user_id'] ?? 0))->first();
        $plan = Plan::query()->where('plan_id', Typed::int($externalRef['plan_id'] ?? 0))->first();

        if (! $user || ! $plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'mercadopago',
            'payment_id_external' => Typed::stringOrNull(data_get($data, 'data.id') ?? null),
            'payment_frequency' => Typed::string($externalRef['frequency'] ?? 'one_time'),
            'payment_type' => 'one_time',
            'base_amount' => Typed::float(data_get($data, 'data.transaction_amount') ?? 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $data['data']['transaction_amount'] ?? 0,
            'currency' => 'BRL',
            'email' => $data['data']['payer']['email'] ?? $user->email,
            'name' => $data['data']['payer']['first_name'] ?? $user->name,
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
