<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Plisio 加密货币支付处理器（规格书 §11）
 */
class PlisioProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        return [
            'processor' => 'plisio',
            'api_key' => config('services.plisio.api_key'),
            'order_number' => 'monit-'.$user->user_id.'-'.time(),
            'order_name' => $plan->name,
            'source_currency' => 'USD',
            'source_amount' => $this->getPrice($plan, $frequency),
            'callback_url' => url('/webhooks/plisio'),
            'success_url' => route('pay.thank_you'),
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $status = $request->input('status');
        if ($status !== 'completed') {
            return null;
        }

        $metadata = json_decode($request->input('data', '{}'), true);

        $user = User::find($metadata['user_id'] ?? 0);
        $plan = Plan::find($metadata['plan_id'] ?? 0);

        if (! $user || ! $plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'plisio',
            'payment_id_external' => $request->input('txn_id'),
            'payment_frequency' => $metadata['frequency'] ?? 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $request->input('source_amount', 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $request->input('source_amount', 0),
            'currency' => $request->input('source_currency', 'USD'),
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
