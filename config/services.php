<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

        'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------\
    | Stripe 支付（规格书 §11）
    |--------------------------------------------------------------------------\
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------\
    | PayPal 支付（规格书 §11）
    |--------------------------------------------------------------------------\
    */
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'sandbox' => env('PAYPAL_SANDBOX', true),
        // Webhook 验签（fail-closed）：未配置 webhook_id 时回调一律拒绝
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    /*
    |--------------------------------------------------------------------------\
    | 社交登录 OAuth（规格书 §12.3）
    |--------------------------------------------------------------------------\
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

        'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
    ],

    // 海外社交登录（规格书 §12.3）
    'facebook' => [
        'client_id' => env('FACEBOOK_APP_ID'),
        'client_secret' => env('FACEBOOK_APP_SECRET'),
    ],

    'discord' => [
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key_path' => env('APPLE_PRIVATE_KEY_PATH'),
    ],

    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
    ],

    // 国内社交登录（规格书 §12.3 新增）
    'qq' => [
        'app_id' => env('QQ_APP_ID'),
        'app_key' => env('QQ_APP_KEY'),
    ],

    'wechat' => [
        'app_id' => env('WECHAT_APP_ID'),
        'app_secret' => env('WECHAT_APP_SECRET'),
    ],

    'weibo' => [
        'app_key' => env('WEIBO_APP_KEY'),
        'app_secret' => env('WEIBO_APP_SECRET'),
    ],

    'gitee' => [
        'client_id' => env('GITEE_CLIENT_ID'),
        'client_secret' => env('GITEE_CLIENT_SECRET'),
    ],

    'feishu' => [
        'app_id' => env('FEISHU_APP_ID'),
        'app_secret' => env('FEISHU_APP_SECRET'),
    ],

    // Razorpay 支付（规格书 §11）
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    // Mollie 支付（规格书 §11）
    'mollie' => [
        'api_key' => env('MOLLIE_API_KEY'),
        'webhook_secret' => env('MOLLIE_WEBHOOK_SECRET'),
    ],

    // Paystack 支付（规格书 §11）
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
    ],

    // Paddle 支付（规格书 §11）
    'paddle' => [
        'vendor_id' => env('PADDLE_VENDOR_ID'),
        'vendor_auth_code' => env('PADDLE_VENDOR_AUTH_CODE'),
        'public_key' => env('PADDLE_PUBLIC_KEY'),
        // Paddle Billing Webhook HMAC 验签（fail-closed）
        'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),
    ],

    // 其他支付处理器（规格书 §11）
    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        // Webhook x-signature 验签（fail-closed）
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],

    // WeChat Pay 支付（规格书 §11：中国，Native 扫码，API v2）
    'wechat_pay' => [
        'app_id' => env('WECHAT_PAY_APP_ID'),
        'mch_id' => env('WECHAT_PAY_MCH_ID'),
        'api_key' => env('WECHAT_PAY_API_KEY'),
    ],

    // Alipay 支付（规格书 §11：中国，电脑网站支付，RSA2）
    'alipay' => [
        'app_id' => env('ALIPAY_APP_ID'),
        'private_key' => env('ALIPAY_PRIVATE_KEY'),
        'alipay_public_key' => env('ALIPAY_PUBLIC_KEY'),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
    ],

    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        // Webhook verif-hash 验签（fail-closed）
        'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
    ],

    'lemonsqueezy' => [
        'api_key' => env('LEMONSQUEEZY_API_KEY'),
        'webhook_secret' => env('LEMONSQUEEZY_WEBHOOK_SECRET'),
    ],

    'yookassa' => [
        'shop_id' => env('YOOKASSA_SHOP_ID'),
        'secret_key' => env('YOOKASSA_SECRET_KEY'),
    ],

    'payu' => [
        'pos_id' => env('PAYU_POS_ID'),
        'second_key' => env('PAYU_SECOND_KEY'),
        'client_secret' => env('PAYU_CLIENT_SECRET'),
    ],

    'iyzico' => [
        'api_key' => env('IYZICO_API_KEY'),
        'secret_key' => env('IYZICO_SECRET_KEY'),
        'base_url' => env('IYZICO_BASE_URL', 'sandbox-api.iyzipay.com'),
        // Webhook HMAC 守门（fail-closed）
        'webhook_secret' => env('IYZICO_WEBHOOK_SECRET'),
    ],

    'cryptocom' => [
        'merchant_id' => env('CRYPTOCOM_MERCHANT_ID'),
        'secret_key' => env('CRYPTOCOM_SECRET_KEY'),
        // Webhook HMAC 守门（fail-closed）
        'webhook_secret' => env('CRYPTOCOM_WEBHOOK_SECRET'),
    ],

    'myfatoorah' => [
        'api_key' => env('MYFATOORAH_API_KEY'),
        'is_test' => env('MYFATOORAH_IS_TEST', true),
        // Webhook HMAC 守门（fail-closed）
        'webhook_secret' => env('MYFATOORAH_WEBHOOK_SECRET'),
    ],

    'klarna' => [
        'username' => env('KLARNA_USERNAME'),
        'password' => env('KLARNA_PASSWORD'),
        'region' => env('KLARNA_REGION', 'eu'),
        // Webhook HMAC 守门（fail-closed）
        'webhook_secret' => env('KLARNA_WEBHOOK_SECRET'),
    ],

    'plisio' => [
        'api_key' => env('PLISIO_API_KEY'),
        // Webhook HMAC 守门（fail-closed）
        'webhook_secret' => env('PLISIO_WEBHOOK_SECRET'),
    ],

    'revolut' => [
        'api_key' => env('REVOLUT_API_KEY'),
        'public_id' => env('REVOLUT_PUBLIC_ID'),
        // Webhook HMAC 守门（fail-closed）
        'webhook_secret' => env('REVOLUT_WEBHOOK_SECRET'),
    ],

    'onepay' => [
        'merchant_code' => env('ONEPAY_MERCHANT_CODE'),
        'merchant_key' => env('ONEPAY_MERCHANT_KEY'),
        // Webhook HMAC 守门（fail-closed）
        'webhook_secret' => env('ONEPAY_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 对象存储（M17 §14.8：Offload 插件扩展，凭据也可在后台「存储卸载」设置组配置）
    |--------------------------------------------------------------------------
    */
    'oss' => [
        'access_key_id' => env('OSS_ACCESS_KEY_ID'),
        'access_key_secret' => env('OSS_ACCESS_KEY_SECRET'),
        'bucket' => env('OSS_BUCKET'),
        'endpoint' => env('OSS_ENDPOINT', 'https://oss-cn-hangzhou.aliyuncs.com'),
    ],

    'cos' => [
        'secret_id' => env('COS_SECRET_ID'),
        'secret_key' => env('COS_SECRET_KEY'),
        'bucket' => env('COS_BUCKET'),
        'region' => env('COS_REGION', 'ap-guangzhou'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 短信验证（M17 §12.5：阿里云/腾讯云；后台「短信验证」设置组优先于此处 env）
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'),
    ],

    'sms_aliyun' => [
        'access_key_id' => env('SMS_ALIYUN_ACCESS_KEY_ID'),
        'access_key_secret' => env('SMS_ALIYUN_ACCESS_KEY_SECRET'),
        'sign_name' => env('SMS_ALIYUN_SIGN_NAME'),
        'template_code' => env('SMS_ALIYUN_TEMPLATE_CODE'),
    ],

    'sms_tencent' => [
        'secret_id' => env('SMS_TENCENT_SECRET_ID'),
        'secret_key' => env('SMS_TENCENT_SECRET_KEY'),
        'sdk_app_id' => env('SMS_TENCENT_SDK_APP_ID'),
        'sign_name' => env('SMS_TENCENT_SIGN_NAME'),
        'template_id' => env('SMS_TENCENT_TEMPLATE_ID'),
    ],

];
