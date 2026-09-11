<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Support\Typed;
use Illuminate\Support\Facades\Http;

/**
 * Paystack 支付处理器（规格书 §11）
 */
class PaystackProcessor
{
    public function isConfigured(): bool
    {
        return (bool) config('services.paystack.secret_key');
    }

    /**
     * @return array<string, mixed>
     */
    public function createOrder(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        $secretKey = Typed::string(config('services.paystack.secret_key'));

        try {
            $response = Http::withToken($secretKey)
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $payment->email,
                    'amount' => (int) ((float) ($payment->total_amount ?? 0) * 100),
                    'currency' => $payment->currency,
                    'callback_url' => $successUrl,
                    'metadata' => [
                        'payment_id' => $payment->payment_id,
                        'cancel_action' => $cancelUrl,
                    ],
                ]);

            $data = Typed::arr($response->json());

            if (($data['status'] ?? false) === true) {
                return [
                    'authorization_url' => Typed::string(data_get($data, 'data.authorization_url')),
                    'reference' => Typed::string(data_get($data, 'data.reference')),
                ];
            }

            return ['error' => Typed::string($data['message'] ?? 'Unknown error')];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
