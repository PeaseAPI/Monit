<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * Klarna 支付处理器（规格书 §11）
 */
class KlarnaProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $region = config('services.klarna.region', 'eu');
        $baseUrl = match ($region) {
            'eu' => 'https://api.klarna.com',
            'us' => 'https://api-na.klarna.com',
            default => 'https://api.klarna.com',
        };

        return [
            'processor' => 'klarna',
            'base_url' => $baseUrl,
            'purchase_country' => $region === 'us' ? 'US' : 'SE',
            'purchase_currency' => $region === 'us' ? 'USD' : 'EUR',
            'order_amount' => (int) ($this->getPrice($plan, $frequency) * 100),
            'order_tax_amount' => 0,
            'order_lines' => [[
                'type' => 'digital',
                'name' => $plan->name,
                'quantity' => 1,
                'unit_price' => (int) ($this->getPrice($plan, $frequency) * 100),
                'total_amount' => (int) ($this->getPrice($plan, $frequency) * 100),
                'total_tax_amount' => 0,
            ]],
            'merchant_urls' => [
                'confirmation' => route('pay.thank_you'),
                'notification' => url('/webhooks/klarna'),
            ],
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $orderId = Typed::string($request->input('order_id'));
        $event = $request->input('event_type');

        if (! in_array($event, ['ORDER_COMPLETED', 'FRAUD_CHECK_ACCEPTED'])) {
            return null;
        }

        // 从 session 或缓存获取 metadata
        $metadata = Typed::arr(cache()->get("klarna_order_{$orderId}"));

        $user = User::query()->where('user_id', Typed::int($metadata['user_id'] ?? 0))->first();
        $plan = Plan::query()->where('plan_id', Typed::int($metadata['plan_id'] ?? 0))->first();

        if (! $user || ! $plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'klarna',
            'payment_id_external' => $orderId,
            'payment_frequency' => Typed::string($metadata['frequency'] ?? 'one_time'),
            'payment_type' => 'one_time',
            'base_amount' => Typed::float($metadata['amount'] ?? 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => Typed::float($metadata['amount'] ?? 0),
            'currency' => Typed::string($metadata['currency'] ?? 'EUR'),
            'email' => $user->email,
            'name' => $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['EUR'] ?? $plan->prices['USD'] ?? $plan->prices;

        return (float) match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
