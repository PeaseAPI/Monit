<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

/**
 * Razorpay 支付处理器（规格书 §11）
 */
class RazorpayProcessor
{
    public function isConfigured(): bool
    {
        return (bool) config('services.razorpay.key_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function createOrder(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        $apiKey = config('services.razorpay.key_id');
        $apiSecret = config('services.razorpay.key_secret');

        try {
            $response = Http::withBasicAuth($apiKey, $apiSecret)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => (int) ((float) ($payment->total_amount ?? 0) * 100),
                    'currency' => $payment->currency,
                    'receipt' => (string) $payment->payment_id,
                    'notes' => ['payment_id' => $payment->payment_id],
                ]);

            $data = $response->json();

            return [
                'order_id' => $data['id'] ?? null,
                'key_id' => $apiKey,
                'amount' => (float) ($payment->total_amount ?? 0) * 100,
                'currency' => $payment->currency,
                'prefill' => [
                    'name' => $payment->name,
                    'email' => $payment->email,
                ],
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
