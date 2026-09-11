<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use App\Support\Typed;
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

        if (! is_string($webhookSecret) || $webhookSecret === '') {
            return response()->json(['error' => 'Not configured'], 400);
        }

        $computed = hash_hmac('sha256', $request->getContent(), $webhookSecret);
        $provided = $request->header('x-razorpay-signature', '');

        if ($provided === '' || ! hash_equals($computed, $provided)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? '';

        if ($event === 'payment.captured') {
            $paymentEntity = Typed::arr(data_get($payload, 'payload.payment.entity', []));
            $paymentId = Typed::intOrNull(data_get($paymentEntity, 'notes.payment_id'));
            $externalId = Typed::stringOrNull($paymentEntity['id'] ?? null);

            // 金额/币种防篡改：amount 为派萨（最小单位），须与本地订单一致方可入账
            if (($paymentId !== 0 && $paymentId !== null)
                && $paymentService->verifyGatewayAmount(
                    $paymentId,
                    PaymentService::majorUnits(
                        Typed::intOrNull($paymentEntity['amount'] ?? null),
                        Typed::string($paymentEntity['currency'] ?? '')
                    ),
                    Typed::string($paymentEntity['currency'] ?? ''),
                    'razorpay',
                )) {
                $paymentService->handlePaymentSuccess($paymentId, Typed::string($externalId));
            }
        }

        // 规格 §6.3.1：支付失败事件派发 webhook_payment_failure_url
        if ($event === 'payment.failed') {
            $paymentEntity = Typed::arr(data_get($payload, 'payload.payment.entity', []));
            $paymentId = Typed::intOrNull(data_get($paymentEntity, 'notes.payment_id'));

            if ($paymentId !== 0 && $paymentId !== null) {
                $paymentService->handlePaymentFailure(
                    $paymentId,
                    Typed::string($paymentEntity['id'] ?? ''),
                    Typed::string($paymentEntity['error_description'] ?? '')
                );
            }
        }

        if ($event === 'subscription.cancelled') {
            $subscriptionEntity = Typed::arr(data_get($payload, 'payload.subscription.entity', []));
            $subscriptionId = Typed::stringOrNull($subscriptionEntity['id'] ?? null);
            if ($subscriptionId !== '' && $subscriptionId !== null) {
                $paymentService->handleSubscriptionCancelled($subscriptionId, 'razorpay');
            }
        }

        return response()->json(['received' => true]);
    }
}
