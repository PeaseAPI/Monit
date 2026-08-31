<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Midtrans 支付处理器（规格书 §11）
 */
class MidtransProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $serverKey = config('services.midtrans.server_key');
        $clientKey = config('services.midtrans.client_key');

        return [
            'processor' => 'midtrans',
            'client_key' => $clientKey,
            'transaction_details' => [
                'order_id' => 'monit-'.$user->user_id.'-'.time(),
                'gross_amount' => $this->getPrice($plan, $frequency),
            ],
            'item_details' => [[
                'id' => $plan->plan_id,
                'price' => $this->getPrice($plan, $frequency),
                'quantity' => 1,
                'name' => $plan->name,
            ]],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'custom_field1' => json_encode([
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ]),
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $customField = json_decode($request->input('custom_field1', '{}'), true);
        $user = User::find($customField['user_id'] ?? 0);
        $plan = Plan::find($customField['plan_id'] ?? 0);

        if (! $user || ! $plan) {
            return null;
        }

        $status = $request->input('transaction_status');
        if (! in_array($status, ['capture', 'settlement'])) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'midtrans',
            'payment_id_external' => $request->input('transaction_id'),
            'payment_frequency' => $customField['frequency'] ?? 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $request->input('gross_amount', 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $request->input('gross_amount', 0),
            'currency' => 'IDR',
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
