<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * PayU 支付处理器（规格书 §11）
 */
class PayUProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $posId = config('services.payu.pos_id');

        return [
            'processor' => 'payu',
            'pos_id' => $posId,
            'ext_order_id' => 'monit-'.$user->user_id.'-'.time(),
            'customer_ip' => request()->ip(),
            'merchant_pos_id' => $posId,
            'description' => $plan->name.' 订阅',
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
        $order = Typed::arr($request->input('order'));
        $status = Typed::string($order['status'] ?? '');

        if ($status !== 'COMPLETED') {
            return null;
        }

        $extOrderId = Typed::string($order['extOrderId'] ?? '');
        $parts = explode('-', $extOrderId);
        $userId = $parts[1] ?? 0;

        $user = User::query()->where('user_id', (int) ($userId))->first();
        if ($user === null) {
            return null;
        }

        $plan = Plan::query()->where('plan_id', (int) ($user->plan_id))->first();
        $totalAmount = Typed::float($order['totalAmount'] ?? 0) / 100;

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => ($plan !== null) ? $plan->plan_id : 'free',
            'processor' => 'payu',
            'payment_id_external' => Typed::stringOrNull($order['orderId'] ?? null),
            'payment_frequency' => 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $totalAmount,
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $totalAmount,
            'currency' => Typed::string($order['currencyCode'] ?? 'PLN'),
            'email' => Typed::stringOrNull(data_get($order, 'buyer.email')) ?? $user->email,
            'name' => Typed::stringOrNull(data_get($order, 'buyer.firstName')) ?? $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['PLN'] ?? $plan->prices['USD'] ?? $plan->prices;

        return (float) match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
