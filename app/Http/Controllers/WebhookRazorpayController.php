<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Razorpay Webhook 控制器（规格书 §11）
 *
 * 官方验签（fail-closed）：X-Razorpay-Signature = HMAC-SHA256(rawBody, webhook_secret)
 * 密钥未配置 / 签名不符 → 400 拒绝
 */
class WebhookRazorpayController extends Controller
{
    public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
    {
        $webhookSecret = config('services.razorpay.webhook_secret');

        if (empty($webhookSecret)) {
            return response()->json(['error' => 'Not configured'], 400);
        }

        $computed = hash_hmac('sha256', $request->getContent(), $webhookSecret);
        $provided = (string) $request->header('x-razorpay-signature', '');

        if ($provided === '' || ! hash_equals($computed, $provided)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? '';

        if ($event === 'payment.captured') {
            $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
            $paymentId = $paymentEntity['notes']['payment_id'] ?? null;
            $externalId = $paymentEntity['id'] ?? null;

            // 金额/币种防篡改：amount 为派萨（最小单位），须与本地订单一致方可入账
            if ($paymentId
                && $paymentService->verifyGatewayAmount(
                    (int) $paymentId,
                    PaymentService::majorUnits(
                        $paymentEntity['amount'] ?? null,
                        (string) ($paymentEntity['currency'] ?? '')
                    ),
                    (string) ($paymentEntity['currency'] ?? ''),
                    'razorpay',
                )) {
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
