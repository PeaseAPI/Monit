<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Mollie\Api\MollieApiClient;

/**
 * Mollie 支付处理器（规格书 §11）
 */
class MollieProcessor
{
    public function isConfigured(): bool
    {
        return (bool) config('services.mollie.key');
    }

    public function createOrder(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        try {
            $mollie = new MollieApiClient;
            $mollie->setApiKey(config('services.mollie.key'));

            $order = $mollie->payments->create([
                'amount' => [
                    'currency' => $payment->currency,
                    'value' => number_format($payment->total_amount, 2, '.', ''),
                ],
                'description' => config('app.name').' - '.$payment->frequency,
                'redirectUrl' => $successUrl,
                'cancelUrl' => $cancelUrl,
                'metadata' => ['payment_id' => $payment->payment_id],
            ]);

            return [
                'checkout_url' => $order->getCheckoutUrl(),
                'payment_id' => $order->id,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
