<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 微信支付/支付宝回调安全回归测试（安全审计轮 #6）：
 * 1. fail-closed：wechat_pay.api_key 未配置时空 key MD5 签名可被任何人复现
 *    （复刻 sign() 算法即可伪造回调）→ 必须拒绝，订单不得入账
 * 2. 金额防篡改：回调金额（total_fee/total_amount，虽被签名覆盖）必须与
 *    订单 total_amount 一致方可入账，防止同商户低额订单嫁接/记账错误
 * 3. alipay 回调公钥未配置显式拒绝（原先依赖 openssl 无效 key 副作用返回 false）
 */
class WechatAlipayWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $planId = 'free'): User
    {
        return User::create([
            'name' => 'Payer',
            'email' => 'payer@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => $planId,
        ]);
    }

    protected function makePlan(string $planId = 'pro'): Plan
    {
        return Plan::create([
            'plan_id' => $planId,
            'name' => ucfirst($planId),
            'order' => 1,
            'is_enabled' => true,
            'prices' => ['CNY' => ['monthly' => 9.99, 'annual' => 99.99, 'lifetime' => 199.99]],
            'settings' => ['no_resources_limit' => -1],
        ]);
    }

    protected function makePendingPayment(User $user, string $processor = 'wechatpay'): Payment
    {
        $this->makePlan('pro');

        return Payment::create([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'plan_id' => 'pro',
            'payment_processor' => $processor,
            'type' => 'one_time',
            'frequency' => 'monthly',
            'status' => 0,
            'total_amount' => 9.99,
            'currency' => 'CNY',
            'datetime' => now(),
        ]);
    }

    /* ---------------- 签名构造（复刻处理器官方算法） ---------------- */

    protected function wechatSign(array $data, string $key): string
    {
        ksort($data);
        $parts = [];
        foreach ($data as $k => $v) {
            if ($k !== 'sign' && $v !== '' && $v !== null) {
                $parts[] = $k.'='.$v;
            }
        }

        return strtoupper(md5(implode('&', $parts).'&key='.$key));
    }

    protected function wechatXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            $xml .= '<'.$k.'>'.$v.'</'.$k.'>';
        }

        return $xml.'</xml>';
    }

    protected function alipaySign(array $data, string $privateKey): string
    {
        ksort($data);
        $parts = [];
        foreach ($data as $k => $v) {
            if ($v !== '' && $v !== null) {
                $parts[] = $k.'='.$v;
            }
        }
        openssl_sign(implode('&', $parts), $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    protected function rsaKeyPair(): array
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privateKey);

        return [$privateKey, openssl_pkey_get_details($res)['key']];
    }

    protected function wechatCallbackData(Payment $payment, string $totalFee): array
    {
        return [
            'appid' => 'wx_test_appid',
            'mch_id' => 'mch_test',
            'nonce_str' => Str::random(16),
            'return_code' => 'SUCCESS',
            'result_code' => 'SUCCESS',
            'out_trade_no' => 'monit_'.$payment->payment_id.'_120000',
            'total_fee' => $totalFee,
            'transaction_id' => '42000'.Str::random(10),
            'attach' => json_encode(['payment_id' => $payment->payment_id]),
        ];
    }

    protected function postWechatXml(array $data, string $xml)
    {
        return $this->call('POST', '/webhooks/wechatpay', [], [], [], [
            'CONTENT_TYPE' => 'text/xml',
        ], $xml);
    }

    /* ---------------- Gap 1：空 key 伪造 ---------------- */

    public function test_wechatpay_rejects_forged_callback_when_api_key_unconfigured(): void
    {
        config()->set('services.wechat_pay.api_key', null);

        $user = $this->makeUser();
        $payment = $this->makePendingPayment($user);

        $data = $this->wechatCallbackData($payment, '999');
        // 空 key 签名：算法公开，api_key 未配置时任何攻击者均可复现
        $data['sign'] = $this->wechatSign($data, '');

        $this->postWechatXml($data, $this->wechatXml($data))->assertStatus(400);

        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 0]);
    }

    public function test_wechatpay_rejects_invalid_signature_when_configured(): void
    {
        config()->set('services.wechat_pay.api_key', 'wx_secret_key');

        $user = $this->makeUser();
        $payment = $this->makePendingPayment($user);

        $data = $this->wechatCallbackData($payment, '999');
        $data['sign'] = strtoupper(Str::random(32)); // 错误签名

        $response = $this->postWechatXml($data, $this->wechatXml($data));

        $response->assertStatus(200);
        $this->assertStringContainsString('FAIL', $response->getContent());
        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 0]);
    }

    /* ---------------- Gap 2：金额比对 ---------------- */

    public function test_wechatpay_rejects_amount_mismatch(): void
    {
        config()->set('services.wechat_pay.api_key', 'wx_secret_key');

        $user = $this->makeUser();
        $payment = $this->makePendingPayment($user); // 订单 9.99 元 = 999 分

        $data = $this->wechatCallbackData($payment, '1'); // 回调仅 1 分
        $data['sign'] = $this->wechatSign($data, 'wx_secret_key');

        $response = $this->postWechatXml($data, $this->wechatXml($data));

        $response->assertStatus(200);
        $this->assertStringContainsString('FAIL', $response->getContent());
        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 0]);
    }

    public function test_wechatpay_accepts_valid_callback(): void
    {
        config()->set('services.wechat_pay.api_key', 'wx_secret_key');

        $user = $this->makeUser();
        $payment = $this->makePendingPayment($user);

        $data = $this->wechatCallbackData($payment, '999');
        $data['sign'] = $this->wechatSign($data, 'wx_secret_key');

        $response = $this->postWechatXml($data, $this->wechatXml($data));

        $response->assertStatus(200);
        $this->assertStringContainsString('SUCCESS', $response->getContent());
        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 1]);
        $this->assertSame('pro', $user->fresh()->plan_id);
    }

    /* ---------------- Alipay：显式配置检查 + 金额比对 ---------------- */

    public function test_alipay_rejects_notify_when_public_key_unconfigured(): void
    {
        config()->set('services.alipay.alipay_public_key', null);

        $user = $this->makeUser();
        $payment = $this->makePendingPayment($user, 'alipay');

        $this->post('/webhooks/alipay', [
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => 'monit_'.$payment->payment_id.'_120000',
            'total_amount' => '9.99',
            'trade_no' => '20240903220010000000',
            'passback_params' => urlencode(json_encode(['payment_id' => $payment->payment_id])),
            'sign' => base64_encode(Str::random(64)),
            'sign_type' => 'RSA2',
        ])->assertStatus(400);

        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 0]);
    }

    public function test_alipay_rejects_amount_mismatch(): void
    {
        [$privateKey, $publicKey] = $this->rsaKeyPair();
        config()->set('services.alipay.alipay_public_key', $publicKey);

        $user = $this->makeUser();
        $payment = $this->makePendingPayment($user, 'alipay'); // 订单 9.99 元

        $data = [
            'app_id' => 'alipay_test_app',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => 'monit_'.$payment->payment_id.'_120000',
            'total_amount' => '0.01', // 回调仅 0.01 元，签名正确
            'trade_no' => '20240903220010000000',
            'passback_params' => urlencode(json_encode(['payment_id' => $payment->payment_id])),
        ];
        $data['sign'] = $this->alipaySign($data, $privateKey);
        $data['sign_type'] = 'RSA2';

        $response = $this->post('/webhooks/alipay', $data);

        $response->assertStatus(200);
        $this->assertSame('fail', $response->getContent());
        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 0]);
    }

    public function test_alipay_accepts_valid_signed_notify(): void
    {
        [$privateKey, $publicKey] = $this->rsaKeyPair();
        config()->set('services.alipay.alipay_public_key', $publicKey);

        $user = $this->makeUser();
        $payment = $this->makePendingPayment($user, 'alipay');

        $data = [
            'app_id' => 'alipay_test_app',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => 'monit_'.$payment->payment_id.'_120000',
            'total_amount' => '9.99',
            'trade_no' => '20240903220010000000',
            'passback_params' => urlencode(json_encode(['payment_id' => $payment->payment_id])),
        ];
        $data['sign'] = $this->alipaySign($data, $privateKey);
        $data['sign_type'] = 'RSA2';

        $response = $this->post('/webhooks/alipay', $data);

        $response->assertStatus(200);
        $this->assertSame('success', $response->getContent());
        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 1]);
        $this->assertSame('pro', $user->fresh()->plan_id);
    }
}

