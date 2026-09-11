<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Http\Request;

/**
 * MyFatoorah 支付处理器（规格书 §11）
 */
class MyFatoorahProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckout(User $user, Plan $plan, string $frequency): array
    {
        $isTest = config('services.myfatoorah.is_test', true);
        $baseUrl = ((bool) $isTest) ? 'https://apitest.myfatoorah.com' : 'https://api.myfatoorah.com';

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
        $data = Typed::arr($request->input('Data'));
        $status = Typed::string($data['TransactionStatus'] ?? '');

        if ($status !== 'SUCCESS') {
            return null;
        }

        $metadataRaw = $data['UserDefinedField'] ?? '{}';
        $metadata = is_string($metadataRaw)
            ? Typed::arr(json_decode($metadataRaw, true))
            : Typed::arr($metadataRaw);

        $user = User::query()->where('user_id', Typed::int($metadata['user_id'] ?? 0))->first();
        $plan = Plan::query()->where('plan_id', Typed::int($metadata['plan_id'] ?? 0))->first();

        if ($user === null || $plan === null) {
            return null;
        }

        return Payment::create([
            'user_id' => $user->user_id,
            'plan_id' => $plan->plan_id,
            'processor' => 'myfatoorah',
            'payment_id_external' => Typed::stringOrNull($data['InvoiceId'] ?? null),
            'payment_frequency' => Typed::string($metadata['frequency'] ?? 'one_time'),
            'payment_type' => 'one_time',
            'base_amount' => Typed::float($data['InvoiceValue'] ?? 0),
            'discount_amount' => 0,
            'taxes_amount' => 0,
            'total_amount' => Typed::float($data['InvoiceValue'] ?? 0),
            'currency' => Typed::string($data['Currency'] ?? 'KWD'),
            'email' => $user->email,
            'name' => Typed::stringOrNull($data['CustomerName'] ?? null) ?? $user->name,
            'datetime' => now(),
        ]);
    }

    private function getPrice(Plan $plan, string $frequency): float
    {
        $prices = $plan->prices['USD'] ?? $plan->prices;

        return (float) match ($frequency) {
            'monthly' => $prices['monthly'] ?? 0,
            'annual' => $prices['annual'] ?? 0,
            'lifetime' => $prices['lifetime'] ?? 0,
            default => $prices['monthly'] ?? 0,
        };
    }
}
