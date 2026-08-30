<?php

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * Paystack 支付处理器（规格书 §11）
 */
class PaystackProcessor
{
    public function isConfigured(): bool
    {
        return (bool) config('services.paystack.secret_key');
    }

    public function createOrder(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        $secretKey = config('services.paystack.secret_key');

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($secretKey)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $payment->email,
                    'amount' => (int) ($payment->total_amount * 100),
                    'currency' => $payment->currency,
                    'callback_url' => $successUrl,
                    'metadata' => [
                        'payment_id' => $payment->payment_id,
                        'cancel_action' => $cancelUrl,
                    ],
                ]);

            $data = $response->json();

            if (($data['status'] ?? false) === true) {
                return [
                    'authorization_url' => $data['data']['authorization_url'],
                    'reference' => $data['data']['reference'],
                ];
            }

            return ['error' => $data['message'] ?? 'Unknown error'];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
