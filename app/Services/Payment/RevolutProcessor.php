<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * Revolut 支付处理器（规格书 §11）
 */
class RevolutProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        return [
            'processor' => 'revolut',
            'public_id' => config('services.revolut.public_id'),
            'order_id' => 'monit-'.$user->user_id.'-'.time(),
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

        $order = Typed::arr($request->input('order'));
        $metadata = Typed::arr($order['metadata'] ?? []);

        $user = User::query()->where('user_id', Typed::int($metadata['user_id'] ?? 0))->first();
        $plan = Plan::query()->where('plan_id', Typed::int($metadata['plan_id'] ?? 0))->first();

        if (! $user || ! $plan) {
            return null;
        }

        $totalAmount = Typed::float($order['total_amount'] ?? 0) / 100;

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'revolut',
            'payment_id_external' => Typed::stringOrNull($order['id'] ?? null),
            'payment_frequency' => Typed::string($metadata['frequency'] ?? 'one_time'),
            'payment_type' => 'one_time',
            'base_amount' => $totalAmount,
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $totalAmount,
            'currency' => Typed::string($order['currency'] ?? 'USD'),
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
