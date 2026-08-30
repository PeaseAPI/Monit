<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * PayU 支付处理器（规格书 §11）
 */
class PayUProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $posId = config('services.payu.pos_id');

        return [
            'processor' => 'payu',
            'pos_id' => $posId,
            'ext_order_id' => 'monit-' . $user->user_id . '-' . time(),
            'customer_ip' => request()->ip(),
            'merchant_pos_id' => $posId,
            'description' => $plan->name . ' 订阅',
            'currency_code' => 'PLN',
            'total_amount' => (int) ($this->getPrice($plan, $frequency) * 100),
            'buyer' => [
                'email' => $user->email,
                'firstName' => $user->name,
            ],
            'products' => [[
                'name' => $plan->name,
                'unitPrice' => (int) ($this->getPrice($plan, $frequency) * 100),
                'quantity' => 1,
            ]],
            'notify_url' => url('/webhooks/payu'),
            'continue_url' => route('pay.thank_you'),
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $order = $request->input('order', []);
        $status = $order['status'] ?? '';

        if ($status !== 'COMPLETED') {
            return null;
        }

        $extOrderId = $order['extOrderId'] ?? '';
        $parts = explode('-', $extOrderId);
        $userId = $parts[1] ?? 0;

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        $plan = Plan::find($user->plan_id);
        $totalAmount = ($order['totalAmount'] ?? 0) / 100;

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan ? $plan->plan_id : 'free',
            'processor' => 'payu',
            'payment_id_external' => $order['orderId'] ?? null,
            'payment_frequency' => 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $totalAmount,
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $totalAmount,
            'currency' => $order['currencyCode'] ?? 'PLN',
            'email' => $order['buyer']['email'] ?? $user->email,
            'name' => $order['buyer']['firstName'] ?? $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['PLN'] ?? $plan->prices['USD'] ?? $plan->prices;
        return match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
