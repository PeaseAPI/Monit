<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use App\Support\Typed;
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
        $signature = $request->header('x-paystack-signature', '');

        // fail-closed：密钥未配置或签名缺失/不符一律拒绝（原实现未配置时放行）
        if (! is_string($secretKey) || $secretKey === '' || $signature === '') {
            return response()->json(['error' => 'Not configured'], 400);
        }

        $computedSignature = hash_hmac('sha512', $request->getContent(), $secretKey);

        if (! hash_equals($computedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = $request->input('event', '');
        $data = Typed::arr($request->input('data'));

        if ($event === 'charge.success') {
            $paymentId = data_get($data, 'metadata.payment_id');
            $externalId = $data['id'] ?? null;

            // 金额/币种防篡改：amount 为分/派萨（最小单位），须与本地订单一致方可入账
            if ((bool) $paymentId
                && $paymentService->verifyGatewayAmount(
                    Typed::int($paymentId),
                    PaymentService::majorUnits(
                        Typed::intOrNull($data['amount'] ?? null),
                        Typed::string($data['currency'] ?? '')),
                    Typed::string($data['currency'] ?? ''),
                    'paystack',
                )) {
                $paymentService->handlePaymentSuccess(Typed::int($paymentId), Typed::string($externalId));
            }
        }

        // 规格 §6.3.1：支付失败事件派发 webhook_payment_failure_url
        if ($event === 'charge.failed') {
            $paymentId = data_get($data, 'metadata.payment_id');

            if ((bool) $paymentId) {
                $paymentService->handlePaymentFailure(
                    Typed::int($paymentId),
                    Typed::string($data['id'] ?? ''),
                    Typed::string(data_get($data, 'gateway_response.message')));
            }
        }

        if ($event === 'subscription.disable') {
            $subscriptionCode = $data['subscription_code'] ?? null;
            if ((bool) $subscriptionCode) {
                $paymentService->handleSubscriptionCancelled(Typed::string($subscriptionCode), 'paystack');
            }
        }

        return response()->json(['received' => true]);
    }
}
