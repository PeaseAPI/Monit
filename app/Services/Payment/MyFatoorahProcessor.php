<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * MyFatoorah 支付处理器（规格书 §11）
 */
class MyFatoorahProcessor
{
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $isTest = config('services.myfatoorah.is_test', true);
        $baseUrl = $isTest ? 'https://apitest.myfatoorah.com' : 'https://api.myfatoorah.com';

        return [
            'processor' => 'myfatoorah',
            'base_url' => $baseUrl,
            'invoice_value' => $this->getPrice($plan, $frequency),
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'notification_option' => 'ALL',
            'language' => 'zh',
            'metadata' => [
                'user_id' => $user->user_id,
                'plan_id' => $plan->plan_id,
                'frequency' => $frequency,
            ],
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        $data = $request->input('Data', []);
        $status = $data['TransactionStatus'] ?? '';

        if ($status !== 'SUCCESS') {
            return null;
        }

        $metadata = $data['UserDefinedField'] ?? '{}';
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }

        $user = User::find($metadata['user_id'] ?? 0);
        $plan = Plan::find($metadata['plan_id'] ?? 0);

        if (!$user || !$plan) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'myfatoorah',
            'payment_id_external' => $data['InvoiceId'] ?? null,
            'payment_frequency' => $metadata['frequency'] ?? 'one_time',
            'payment_type' => 'one_time',
            'base_amount' => $data['InvoiceValue'] ?? 0,
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => $data['InvoiceValue'] ?? 0,
            'currency' => $data['Currency'] ?? 'KWD',
            'email' => $user->email,
            'name' => $data['CustomerName'] ?? $user->name,
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
