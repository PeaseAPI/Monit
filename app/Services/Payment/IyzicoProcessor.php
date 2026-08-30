<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * iyzico 支付处理器（规格书 §11）
 */
class IyzicoProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $baseUrl = config('services.iyzico.base_url', 'sandbox-api.iyzipay.com');

        return [
            'processor' => 'iyzico',
            'base_url' => $baseUrl,
            'conversation_id' => 'monit-' . $user->user_id . '-' . time(),
            'price' => $this->getPrice($plan, $frequency),
            'paid_price' => $this->getPrice($plan, $frequency),
            'currency' => 'TRY',
            'basket_id' => 'B' . time(),
            'buyer' => [
                'id' => (string) $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'basket_items' => [[
                'id' => $plan->plan_id,
                'name' => $plan->name,
                'category1' => 'SaaS',
                'category2' => 'Analytics',
                'itemType' => 'VIRTUAL',
                'price' => $this->getPrice($plan, $frequency),
            ]],
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
        if ($status !== 'SUCCESS') {
            return null;
        }

        $conversationId = $request->input('conversationId', '');
        $parts = explode('-', $conversationId);
        $userId = $parts[1] ?? 0;

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $plan = Plan::find($user->plan_id);

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan ? $plan->plan_id : 'free',
            'processor' => 'iyzico',
            'payment_id_external' => $request->input('paymentId'),
            'payment_frequency' => 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $request->input('price', 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $request->input('paidPrice', 0),
            'currency' => 'TRY',
            'email' => $user->email,
            'name' => $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['TRY'] ?? $plan->prices['USD'] ?? $plan->prices;
        return match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
