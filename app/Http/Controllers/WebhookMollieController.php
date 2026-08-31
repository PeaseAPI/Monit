<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
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
            if ($apiKey) {
                try {
                    $mollie = new MollieApiClient;
                    $mollie->setApiKey($apiKey);
                    $payment = $mollie->payments->get($paymentId);

                    if ($payment->isPaid()) {
                        $internalPaymentId = $payment->metadata->payment_id ?? null;
                        if ($internalPaymentId) {
                            $paymentService->handlePaymentSuccess((int) $internalPaymentId, $paymentId);
                        }
                    }

                    // 规格 §6.3.1：支付失败事件派发 webhook_payment_failure_url
                    if ($payment->isFailed()) {
                        $internalPaymentId = $payment->metadata->payment_id ?? null;
                        if ($internalPaymentId) {
                            $paymentService->handlePaymentFailure((int) $internalPaymentId, $paymentId);
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
