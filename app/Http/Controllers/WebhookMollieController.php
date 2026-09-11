<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use App\Support\Typed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mollie\Api\MollieApiClient;

/**
 * Mollie Webhook 控制器（规格书 §11）
 */
class WebhookMollieController extends Controller
{
    public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
    {
        $paymentId = $request->input('id');

        if ($paymentId) {
            $apiKey = config('services.mollie.key');
            if (is_string($apiKey) && $apiKey !== '') {
                try {
                    $mollie = new MollieApiClient;
                    $mollie->setApiKey($apiKey);
                    $payment = $mollie->payments->get(Typed::string($paymentId));

                    if ($payment->isPaid()) {
                        $internalPaymentId = Typed::intOrNull($payment->metadata->payment_id ?? null);

                        // 金额防篡改（安全审计周期 #19）：金额来自 Mollie API 服务端回查
                        // （可信），但仍须与本地订单一致方可入账（错单/改价 fail-closed）
                        if ($internalPaymentId
                            && $paymentService->verifyGatewayAmount(
                                $internalPaymentId,
                                Typed::float($payment->amount->value ?? 0),
                                Typed::string($payment->amount->currency ?? ''),
                                'mollie',
                            )) {
                            $paymentService->handlePaymentSuccess($internalPaymentId, Typed::string($paymentId));
                        }
                    }

                    // 规格 §6.3.1：支付失败事件派发 webhook_payment_failure_url
                    if ($payment->isFailed()) {
                        $internalPaymentId = Typed::intOrNull($payment->metadata->payment_id ?? null);
                        if ($internalPaymentId) {
                            $paymentService->handlePaymentFailure($internalPaymentId, Typed::string($paymentId));
                        }
                    }
                } catch (\Throwable) {
                    // 静默处理
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
