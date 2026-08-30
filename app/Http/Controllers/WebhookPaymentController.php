<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 通用支付 Webhook 控制器（规格书 §11：其余处理器）
 */
class WebhookPaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function paddle(Request $request): JsonResponse
    {
        if ($request->input('alert_name') === 'payment_succeeded') {
            $data = json_decode($request->input('passthrough', ''), true) ?? [];
            $paymentId = $data['payment_id'] ?? null;
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('order_id'));
            }
        }
        return response()->json(['received' => true]);
    }

    public function paddleBilling(Request $request): JsonResponse
    {
        if ($request->input('event_type') === 'transaction.completed') {
            $paymentId = $request->input('data.custom_data.payment_id');
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('data.id'));
            }
        }
        return response()->json(['received' => true]);
    }

    public function mercadopago(Request $request): JsonResponse
    {
        $action = $request->input('action', '');
        if (in_array($action, ['payment.created', 'payment.updated'])) {
            $this->paymentService->handleExternalPaymentNotification('mercadopago', (string) $request->input('data.id'));
        }
        return response()->json(['received' => true]);
    }

    public function midtrans(Request $request): JsonResponse
    {
        if (in_array($request->input('transaction_status'), ['capture', 'settlement'])) {
            $this->paymentService->handleExternalPaymentNotification('midtrans', $request->input('order_id', ''));
        }
        return response()->json(['received' => true]);
    }

    public function flutterwave(Request $request): JsonResponse
    {
        $secretHash = config('services.flutterwave.secret_hash');
        if ($secretHash && $request->header('verif-hash') !== $secretHash) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        if ($request->input('event.type') === 'CARD_TRANSACTION.COMPLETED') {
            $this->paymentService->handleExternalPaymentNotification('flutterwave', $request->input('data.tx_ref', ''));
        }
        return response()->json(['received' => true]);
    }

    public function lemonsqueezy(Request $request): JsonResponse
    {
        $secret = config('services.lemonsqueezy.webhook_secret');
        if ($secret) {
            $computed = hash_hmac('sha256', $request->getContent(), $secret);
            if (!hash_equals($computed, $request->header('x-signature', ''))) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }
        if ($request->input('meta.event_name') === 'order_created') {
            $paymentId = $request->input('data.attributes.custom_data.payment_id');
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('data.id'));
            }
        }
                return response()->json(['received' => true]);
    }

    public function yookassa(Request $request): JsonResponse
    {
        if ($request->input('event') === 'payment.succeeded') {
            $paymentId = $request->input('object.metadata.payment_id');
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('object.id'));
            }
        }
        return response()->json(['received' => true]);
    }

    public function payu(Request $request): JsonResponse
    {
        if ($request->input('order.status') === 'COMPLETED') {
            $this->paymentService->handleExternalPaymentNotification('payu', $request->input('order.extOrderId', ''));
        }
        return response()->json(['received' => true]);
    }

    public function iyzico(Request $request): JsonResponse
    {
        if ($request->input('status') === 'SUCCESS') {
            $this->paymentService->handleExternalPaymentNotification('iyzico', $request->input('conversationId', ''));
        }
        return response()->json(['received' => true]);
    }

    public function crypto(Request $request): JsonResponse
    {
        $data = $request->input('object', []);
        if ($request->input('type') === 'payment.created' && ($data['status'] ?? '') === 'completed') {
            $paymentId = $data['metadata']['payment_id'] ?? null;
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) ($data['id'] ?? ''));
            }
        }
        return response()->json(['received' => true]);
    }

    public function myfatoorah(Request $request): JsonResponse
    {
        if ($request->input('EventType') === 'TransactionStatusChanged') {
            $this->paymentService->handleExternalPaymentNotification('myfatoorah', (string) $request->input('Data.InvoiceId'));
        }
        return response()->json(['received' => true]);
    }

    public function klarna(Request $request): JsonResponse
    {
        if ($request->input('event_type') === 'ORDER_COMPLETED') {
            $this->paymentService->handleExternalPaymentNotification('klarna', $request->input('order_id', ''));
        }
        return response()->json(['received' => true]);
    }

    public function plisio(Request $request): JsonResponse
    {
        if (in_array($request->input('status'), ['completed', 'mismatched'])) {
            $this->paymentService->handleExternalPaymentNotification('plisio', $request->input('order_number', ''));
        }
        return response()->json(['received' => true]);
    }

    public function revolut(Request $request): JsonResponse
    {
        if ($request->input('event') === 'ORDER_COMPLETED') {
            $paymentId = $request->input('data.metadata.payment_id');
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('data.id'));
            }
        }
        return response()->json(['received' => true]);
    }

    public function onepay(Request $request): JsonResponse
    {
        $status = $request->input('vnp_ResponseCode', $request->input('status', ''));
        if ($status === '00' || $status === 'completed') {
            $orderId = $request->input('vnp_TxnRef', $request->input('order_id', ''));
            $this->paymentService->handleExternalPaymentNotification('onepay', $orderId);
        }
        return response()->json(['received' => true]);
    }

    /**
     * WeChat Pay Native 回调（XML，规格书 §11：中国）
     */
    public function wechatPay(Request $request)
    {
        $xml = simplexml_load_string((string) $request->getContent());
        $data = $xml ? (array) $xml : [];

        $processor = new \App\Services\Payment\WeChatPayProcessor();

        if (($data['return_code'] ?? '') === 'SUCCESS'
            && ($data['result_code'] ?? '') === 'SUCCESS'
            && $processor->verifyCallback($data)) {

            $attach = json_decode((string) ($data['attach'] ?? '{}'), true);
            $paymentId = (int) ($attach['payment_id'] ?? 0);

            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess($paymentId, (string) ($data['transaction_id'] ?? ''));
            }

            // 微信要求应答 XML success
            return response('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>', 200)
                ->header('Content-Type', 'text/xml');
        }

        return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[Signature mismatch]]></return_msg></xml>', 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Alipay 异步通知（规格书 §11：中国）
     */
    public function alipay(Request $request)
    {
        $processor = new \App\Services\Payment\AlipayProcessor();

        $data = $request->all();

        if (($data['trade_status'] ?? '') === 'TRADE_SUCCESS'
            && $processor->verifyNotify($data)) {

            $outTradeNo = (string) ($data['out_trade_no'] ?? '');
            $passback = json_decode(urldecode((string) ($data['passback_params'] ?? '{}')), true);
            $paymentId = (int) ($passback['payment_id'] ?? 0);

            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess($paymentId, (string) ($data['trade_no'] ?? $outTradeNo));
            }

            return $processor->successResponse();
        }

        return response('fail', 200)->header('Content-Type', 'text/plain');
    }
}

