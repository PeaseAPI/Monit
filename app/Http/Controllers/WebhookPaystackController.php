<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Paystack Webhook 控制器（规格书 §11）
 */
class WebhookPaystackController extends Controller
{
    public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
    {
        $secretKey = config('services.paystack.secret_key');
        $signature = $request->header('x-paystack-signature');

        if ($secretKey && $signature) {
            $computedSignature = hash_hmac('sha512', $request->getContent(), $secretKey);
            if (! hash_equals($computedSignature, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $event = $request->input('event', '');
        $data = $request->input('data', []);

        if ($event === 'charge.success') {
            $metadata = $data['metadata'] ?? [];
            $paymentId = $metadata['payment_id'] ?? null;
            $externalId = $data['id'] ?? null;

            if ($paymentId) {
                $paymentService->handlePaymentSuccess((int) $paymentId, (string) $externalId);
            }
        }

        // 规格 §6.3.1：支付失败事件派发 webhook_payment_failure_url
        if ($event === 'charge.failed') {
            $metadata = $data['metadata'] ?? [];
            $paymentId = $metadata['payment_id'] ?? null;

            if ($paymentId) {
                $gatewayResponse = $data['gateway_response'] ?? [];
                $paymentService->handlePaymentFailure(
                    (int) $paymentId,
                    (string) ($data['id'] ?? ''),
                    (string) ($gatewayResponse['message'] ?? '')
                );
            }
        }

        if ($event === 'subscription.disable') {
            $subscriptionCode = $data['subscription_code'] ?? null;
            if ($subscriptionCode) {
                $paymentService->handleSubscriptionCancelled($subscriptionCode, 'paystack');
            }
        }

        return response()->json(['received' => true]);
    }
}
