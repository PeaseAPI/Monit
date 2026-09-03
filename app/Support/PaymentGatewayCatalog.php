<?php

namespace App\Support;

/**
 * 支付网关密钥目录（后台「支付网关密钥」设置组的唯一事实源）
 *
 * 关联：上游 AdminSettings::update(payment_gateways)；下游 .env(EnvWriter) / config/services.php
 *
 * 安全：keys() 即白名单——只有此处登记的 env 键可经后台写入 .env，
 * 请求中携带的其他键（如 APP_KEY / DB_PASSWORD）一律忽略。
 * webhook_keys 用于视图渲染「验签已配置/未配置」徽章（fail-closed 语义）。
 */
class PaymentGatewayCatalog
{
    /**
     * 网关 => ['keys' => [ENV键 => 类型], 'webhook_keys' => [...]]
     * 类型：password（密钥，掩码显示）/ text / bool（true/false 下拉）
     */
    public static function gateways(): array
    {
        return [
            'stripe' => [
                'keys' => ['STRIPE_KEY' => 'text', 'STRIPE_SECRET' => 'password', 'STRIPE_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['STRIPE_WEBHOOK_SECRET'],
            ],
            'paypal' => [
                'keys' => ['PAYPAL_CLIENT_ID' => 'text', 'PAYPAL_CLIENT_SECRET' => 'password', 'PAYPAL_SANDBOX' => 'bool', 'PAYPAL_WEBHOOK_ID' => 'text'],
                'webhook_keys' => ['PAYPAL_WEBHOOK_ID'],
            ],
            'razorpay' => [
                'keys' => ['RAZORPAY_KEY_ID' => 'text', 'RAZORPAY_KEY_SECRET' => 'password', 'RAZORPAY_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['RAZORPAY_WEBHOOK_SECRET'],
            ],
            'mollie' => [
                'keys' => ['MOLLIE_API_KEY' => 'password', 'MOLLIE_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => [],
            ],
            'paystack' => [
                'keys' => ['PAYSTACK_PUBLIC_KEY' => 'text', 'PAYSTACK_SECRET_KEY' => 'password', 'PAYSTACK_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['PAYSTACK_WEBHOOK_SECRET'],
            ],
            'paddle' => [
                'keys' => ['PADDLE_VENDOR_ID' => 'text', 'PADDLE_VENDOR_AUTH_CODE' => 'password', 'PADDLE_PUBLIC_KEY' => 'text', 'PADDLE_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['PADDLE_PUBLIC_KEY', 'PADDLE_WEBHOOK_SECRET'],
            ],
            'mercadopago' => [
                'keys' => ['MERCADOPAGO_ACCESS_TOKEN' => 'password', 'MERCADOPAGO_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['MERCADOPAGO_WEBHOOK_SECRET'],
            ],
            'midtrans' => [
                'keys' => ['MIDTRANS_SERVER_KEY' => 'password', 'MIDTRANS_CLIENT_KEY' => 'text'],
                'webhook_keys' => ['MIDTRANS_SERVER_KEY'],
            ],
            'flutterwave' => [
                'keys' => ['FLUTTERWAVE_PUBLIC_KEY' => 'text', 'FLUTTERWAVE_SECRET_KEY' => 'password', 'FLUTTERWAVE_SECRET_HASH' => 'password'],
                'webhook_keys' => ['FLUTTERWAVE_SECRET_HASH'],
            ],
            'lemonsqueezy' => [
                'keys' => ['LEMONSQUEEZY_API_KEY' => 'password', 'LEMONSQUEEZY_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['LEMONSQUEEZY_WEBHOOK_SECRET'],
            ],
            'yookassa' => [
                'keys' => ['YOOKASSA_SHOP_ID' => 'text', 'YOOKASSA_SECRET_KEY' => 'password'],
                'webhook_keys' => ['YOOKASSA_SECRET_KEY'],
            ],
            'payu' => [
                'keys' => ['PAYU_POS_ID' => 'text', 'PAYU_SECOND_KEY' => 'password', 'PAYU_CLIENT_SECRET' => 'password'],
                'webhook_keys' => ['PAYU_SECOND_KEY'],
            ],
            'iyzico' => [
                'keys' => ['IYZICO_API_KEY' => 'text', 'IYZICO_SECRET_KEY' => 'password', 'IYZICO_BASE_URL' => 'text', 'IYZICO_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['IYZICO_WEBHOOK_SECRET'],
            ],
            'cryptocom' => [
                'keys' => ['CRYPTOCOM_MERCHANT_ID' => 'text', 'CRYPTOCOM_SECRET_KEY' => 'password', 'CRYPTOCOM_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['CRYPTOCOM_WEBHOOK_SECRET'],
            ],
            'myfatoorah' => [
                'keys' => ['MYFATOORAH_API_KEY' => 'password', 'MYFATOORAH_IS_TEST' => 'bool', 'MYFATOORAH_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['MYFATOORAH_WEBHOOK_SECRET'],
            ],
            'klarna' => [
                'keys' => ['KLARNA_USERNAME' => 'text', 'KLARNA_PASSWORD' => 'password', 'KLARNA_REGION' => 'text', 'KLARNA_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['KLARNA_WEBHOOK_SECRET'],
            ],
            'plisio' => [
                'keys' => ['PLISIO_API_KEY' => 'password', 'PLISIO_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['PLISIO_WEBHOOK_SECRET'],
            ],
            'revolut' => [
                'keys' => ['REVOLUT_API_KEY' => 'password', 'REVOLUT_PUBLIC_ID' => 'text', 'REVOLUT_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['REVOLUT_WEBHOOK_SECRET'],
            ],
            'onepay' => [
                'keys' => ['ONEPAY_MERCHANT_CODE' => 'text', 'ONEPAY_MERCHANT_KEY' => 'password', 'ONEPAY_WEBHOOK_SECRET' => 'password'],
                'webhook_keys' => ['ONEPAY_WEBHOOK_SECRET'],
            ],
            'wechat_pay' => [
                'keys' => ['WECHAT_PAY_APP_ID' => 'text', 'WECHAT_PAY_MCH_ID' => 'text', 'WECHAT_PAY_API_KEY' => 'password'],
                'webhook_keys' => ['WECHAT_PAY_API_KEY'],
            ],
            'alipay' => [
                'keys' => ['ALIPAY_APP_ID' => 'text', 'ALIPAY_PRIVATE_KEY' => 'password', 'ALIPAY_PUBLIC_KEY' => 'password'],
                'webhook_keys' => ['ALIPAY_PUBLIC_KEY'],
            ],
        ];
    }

    /**
     * 全部可经后台写入的 env 键（扁平白名单）
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        $keys = [];

        foreach (self::gateways() as $meta) {
            foreach (array_keys($meta['keys']) as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * 布尔型键（env true/false 语义）
     *
     * @return list<string>
     */
    public static function boolKeys(): array
    {
        $bools = [];

        foreach (self::gateways() as $meta) {
            foreach ($meta['keys'] as $key => $type) {
                if ($type === 'bool') {
                    $bools[] = $key;
                }
            }
        }

        return $bools;
    }

    /**
     * 密钥型键（type=password：机密值，页面掩码显示、空提交保持不变）
     *
     * 上游 AdminSettings::updatePaymentGateways 依此区分「空值=清除」
     * 与「空值=保持不变」语义（text 类型为公开 ID，维持清空即删）。
     *
     * @return list<string>
     */
    public static function passwordKeys(): array
    {
        $secrets = [];

        foreach (self::gateways() as $meta) {
            foreach ($meta['keys'] as $key => $type) {
                if ($type === 'password') {
                    $secrets[] = $key;
                }
            }
        }

        return $secrets;
    }
}
