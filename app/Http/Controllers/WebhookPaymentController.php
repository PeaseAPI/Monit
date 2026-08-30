<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use App\Support\WebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 通用支付 Webhook 控制器（规格书 §11：其余处理器）
 *
 * 安全基线（fail-closed）：所有回调必须在「密钥已配置且签名/回查通过」后才能入账，
 * 密钥未配置 → 直接 400 拒绝（而非放行），杜绝伪造支付通知 0 元激活套餐。
 * 官方算法见 App\Support\WebhookSignature。
 */
class WebhookPaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Paddle 经典（alert_name 体系）：官方 RSA 公钥验签（p_signature）
     */
    public function paddle(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyPaddleClassic($request->all(), (string) config('services.paddle.public_key'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('alert_name') === 'payment_succeeded') {
            $data = json_decode($request->input('passthrough', ''), true) ?? [];
            $paymentId = $data['payment_id'] ?? null;
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('order_id'));
            }
        }
        return response()->json(['received' => true]);
    }

    /**
     * Paddle Billing：官方 HMAC-SHA256(rawBody, secret) vs Signature 头（hex）
     */
    public function paddleBilling(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyHmacHeader($request, config('services.paddle.webhook_secret'), 'Signature')) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('event_type') === 'transaction.completed') {
            $paymentId = $request->input('data.custom_data.payment_id');
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('data.id'));
            }
        }
        return response()->json(['received' => true]);
    }

    /**
     * MercadoPago：官方 x-signature（HMAC-SHA256 of "id:{data.id};request-id:{x-request-id}"）
     */
    public function mercadopago(Request $request): JsonResponse
    {
        if (! $this->verifyMercadoPagoSignature($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $action = $request->input('action', '');
        if (in_array($action, ['payment.created', 'payment.updated'])) {
            $this->paymentService->handleExternalPaymentNotification('mercadopago', (string) $request->input('data.id'));
        }
        return response()->json(['received' => true]);
    }

    /**
     * Midtrans：官方 signature_key = sha512(serverKey.order_id.status_code.gross_amount)
     */
    public function midtrans(Request $request): JsonResponse
    {
        $serverKey = config('services.midtrans.server_key');

        if (empty($serverKey)) {
            return response()->json(['error' => 'Not configured'], 400);
        }

        $expected = hash('sha512', $serverKey . $request->input('order_id', '') . $request->input('status_code', '') . $request->input('gross_amount', ''));

        if (! hash_equals($expected, (string) $request->input('signature_key', ''))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if (in_array($request->input('transaction_status'), ['capture', 'settlement'])) {
            $this->paymentService->handleExternalPaymentNotification('midtrans', $request->input('order_id', ''));
        }
        return response()->json(['received' => true]);
    }

    public function flutterwave(Request $request): JsonResponse
    {
        $secretHash = config('services.flutterwave.secret_hash');

        // fail-closed：未配置 secret 一律拒绝（原实现未配置时放行）
        if (empty($secretHash) || ! hash_equals((string) $secretHash, (string) $request->header('verif-hash'))) {
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

        // fail-closed：未配置 secret 一律拒绝（原实现未配置时放行）
        if (! WebhookSignature::verifyHmacHeader($request, $secret, 'X-Signature')) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('meta.event_name') === 'order_created') {
            $paymentId = $request->input('data.attributes.custom_data.payment_id');
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('data.id'));
            }
        }
        return response()->json(['received' => true]);
    }

    /**
     * YooKassa：通知无签名，官方要求服务端回查支付状态；
     * 同时比对回查响应的 metadata.payment_id，防止「真交易 id + 伪造 metadata」嫁接
     */
    public function yookassa(Request $request): JsonResponse
    {
        if ($request->input('event') !== 'payment.succeeded') {
            return response()->json(['received' => true]);
        }

        $paymentId = $request->input('object.metadata.payment_id');
        $externalId = (string) $request->input('object.id');

        $verified = WebhookSignature::fetchYooKassaPayment(
            $externalId,
            config('services.yookassa.shop_id'),
            config('services.yookassa.secret_key')
        );

        if ($verified === null || (string) ($verified['metadata']['payment_id'] ?? '') !== (string) $paymentId) {
            return response()->json(['error' => 'Verification failed'], 400);
        }

        if ($paymentId) {
            $this->paymentService->handlePaymentSuccess((int) $paymentId, $externalId);
        }
        return response()->json(['received' => true]);
    }

    /**
     * PayU（波兰）：官方 OpenPayU-Signature，md5(rawBody + secondKey)
     */
    public function payu(Request $request): JsonResponse
    {
        $secondKey = config('services.payu.second_key');

        if (empty($secondKey)) {
            return response()->json(['error' => 'Not configured'], 400);
        }

        // 头格式：sender=...;signature=<md5>;content-type=...
        $signatureHeader = (string) $request->header('openpayu-signature', '');
        preg_match('/signature=([^;]+)/i', $signatureHeader, $matches);
        $provided = $matches[1] ?? '';

        $expected = md5($request->getContent() . $secondKey);

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('order.status') === 'COMPLETED') {
            $this->paymentService->handleExternalPaymentNotification('payu', $request->input('order.extOrderId', ''));
        }
        return response()->json(['received' => true]);
    }

    /**
     * Iyzico：官方通知无本地签名可用，采用共享密钥 HMAC 守门（fail-closed）；
     * 生产接入时如官方提供回查接口，应替换为服务端回查。
     */
    public function iyzico(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyHmacHeader($request, config('services.iyzico.webhook_secret'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('status') === 'SUCCESS') {
            $this->paymentService->handleExternalPaymentNotification('iyzico', $request->input('conversationId', ''));
        }
        return response()->json(['received' => true]);
    }

    /**
     * Crypto.com：官方通知无标准签名头，采用共享密钥 HMAC 守门（fail-closed）
     */
    public function crypto(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyHmacHeader($request, config('services.cryptocom.webhook_secret'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $data = $request->input('object', []);
        if ($request->input('type') === 'payment.created' && ($data['status'] ?? '') === 'completed') {
            $paymentId = $data['metadata']['payment_id'] ?? null;
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) ($data['id'] ?? ''));
            }
        }
        return response()->json(['received' => true]);
    }

    /**
     * MyFatoorah：采用共享密钥 HMAC 守门（fail-closed）；生产接入应替换为官方签名/IP 白名单
     */
    public function myfatoorah(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyHmacHeader($request, config('services.myfatoorah.webhook_secret'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('EventType') === 'TransactionStatusChanged') {
            $this->paymentService->handleExternalPaymentNotification('myfatoorah', (string) $request->input('Data.InvoiceId'));
        }
        return response()->json(['received' => true]);
    }

    /**
     * Klarna：采用共享密钥 HMAC 守门（fail-closed）；生产接入应替换为官方签名方案
     */
    public function klarna(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyHmacHeader($request, config('services.klarna.webhook_secret'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('event_type') === 'ORDER_COMPLETED') {
            $this->paymentService->handleExternalPaymentNotification('klarna', $request->input('order_id', ''));
        }
        return response()->json(['received' => true]);
    }

    /**
     * Plisio：采用共享密钥 HMAC 守门（fail-closed）
     */
    public function plisio(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyHmacHeader($request, config('services.plisio.webhook_secret'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if (in_array($request->input('status'), ['completed', 'mismatched'])) {
            $this->paymentService->handleExternalPaymentNotification('plisio', $request->input('order_number', ''));
        }
        return response()->json(['received' => true]);
    }

    /**
     * Revolut：采用共享密钥 HMAC 守门（fail-closed）；官方亦支持公钥验签，生产可替换
     */
    public function revolut(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyHmacHeader($request, config('services.revolut.webhook_secret'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($request->input('event') === 'ORDER_COMPLETED') {
            $paymentId = $request->input('data.metadata.payment_id');
            if ($paymentId) {
                $this->paymentService->handlePaymentSuccess((int) $paymentId, (string) $request->input('data.id'));
            }
        }
        return response()->json(['received' => true]);
    }

    /**
     * OnePay（VNPay 协议）：采用共享密钥 HMAC 守门（fail-closed）；
     * VNPAy 官方为 secure_hash = HMAC-SHA512(secret, 按键序拼接字段)，生产接入应替换为官方算法
     */
    public function onepay(Request $request): JsonResponse
    {
        if (! WebhookSignature::verifyHmacHeader($request, config('services.onepay.webhook_secret'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $status = $request->input('vnp_ResponseCode', $request->input('status', ''));
        if ($status === '00' || $status === 'completed') {
            $orderId = $request->input('vnp_TxnRef', $request->input('order_id', ''));
            $this->paymentService->handleExternalPaymentNotification('onepay', $orderId);
        }
        return response()->json(['received' => true]);
    }

    /**
     * WeChat Pay Native 回调（XML，规格书 §11：中国）
     * LIBXML_NONET 禁止网络实体解析（XXE 防御）
     */
    public function wechatPay(Request $request)
    {
        $xml = simplexml_load_string((string) $request->getContent(), null, LIBXML_NONET);
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

    /* -----------------------------------------------------------------
     | 私有：验签辅助
     ----------------------------------------------------------------- */

    /**
     * MercadoPago 官方 x-signature 验证（fail-closed）
     * 头格式：x-signature: ts=<ts>,v1=<hmac>
     * manifest = "id:{data.id};request-id:{x-request-id}"（官方模板，ts 变体兼容）
     */
    private function verifyMercadoPagoSignature(Request $request): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (empty($secret)) {
            return false;
        }

        $header = (string) $request->header('x-signature', '');
        if ($header === '') {
            return false;
        }

        $ts = '';
        $v1 = '';
        foreach (explode(',', $header) as $segment) {
            $kv = explode('=', trim($segment), 2);
            if (count($kv) !== 2) {
                continue;
            }
            if (trim($kv[0]) === 'ts') {
                $ts = trim($kv[1]);
            }
            if (trim($kv[0]) === 'v1') {
                $v1 = trim($kv[1]);
            }
        }

        if ($v1 === '') {
            return false;
        }

        $dataId = (string) $request->input('data.id', '');
        $requestId = (string) $request->header('x-request-id', '');

        $manifests = ["id:{$dataId};request-id:{$requestId};"];

        // 官方部分账户启用 ts 变体："id:..;request-id:..;ts:..;"
        if ($ts !== '') {
            $manifests[] = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        }

        foreach ($manifests as $manifest) {
            if (hash_equals(hash_hmac('sha256', $manifest, $secret), $v1)) {
                return true;
            }
        }

        return false;
    }
}

