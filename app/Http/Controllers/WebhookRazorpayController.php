<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Razorpay Webhook 控制器（规格书 §11）
 */
class WebhookRazorpayController extends Controller
{
    public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['event'] ?? '';

        if ($event === 'payment.captured') {
            $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
            $paymentId = $paymentEntity['notes']['payment_id'] ?? null;
            $externalId = $paymentEntity['id'] ?? null;

            if ($paymentId) {
                $paymentService->handlePaymentSuccess((int) $paymentId, $externalId);
            }
        }

        // 规格 §6.3.1：支付失败事件派发 webhook_payment_failure_url
        if ($event === 'payment.failed') {
            $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
            $paymentId = $paymentEntity['notes']['payment_id'] ?? null;

            if ($paymentId) {
                $paymentService->handlePaymentFailure(
                    (int) $paymentId,
                    (string) ($paymentEntity['id'] ?? ''),
                    (string) ($paymentEntity['error_description'] ?? '')
                );
            }
        }

        if ($event === 'subscription.cancelled') {
            $subscriptionEntity = $payload['payload']['subscription']['entity'] ?? [];
            $subscriptionId = $subscriptionEntity['id'] ?? null;
            if ($subscriptionId) {
                $paymentService->handleSubscriptionCancelled($subscriptionId, 'razorpay');
            }
        }

        return response()->json(['received' => true]);
    }
}
