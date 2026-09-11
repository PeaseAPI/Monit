<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * Paddle 支付处理器（规格书 §11）
 */
class PaddleProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $paddleConfig = Typed::arr(config('services.paddle'));

        return [
            'processor' => 'paddle',
            'vendor_id' => Typed::string($paddleConfig['vendor_id'] ?? ''),
            'product_id' => $plan->settings['paddle_product_id'] ?? null,
            'custom_message' => $plan->name,
            'passthrough' => json_encode([
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ]),
        ];
    }

    public function handleWebhook(Request $request): Payment
    {
        $passthrough = Typed::arr(json_decode(Typed::string($request->input('passthrough', '{}')), true));
        $user = User::query()->where('user_id', Typed::int($passthrough['user_id'] ?? 0))->firstOrFail();
        $plan = Plan::query()->where('plan_id', Typed::int($passthrough['plan_id'] ?? 0))->firstOrFail();

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'paddle',
            'payment_id_external' => Typed::string($request->input('order_id')),
            'payment_frequency' => Typed::string($passthrough['frequency'] ?? 'one_time'),
            'payment_type' => $request->input('alert_name') === 'subscription_created' ? 'recurring' : 'one_time',
            'base_amount' => Typed::float($request->input('sale_gross', 0)),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $request->input('sale_gross', 0),
            'currency' => $request->input('currency', 'USD'),
            'email' => $request->input('email', $user->email),
            'name' => $request->input('customer_name', $user->name),
            'datetime' => now(),
        ]);
    }
}
