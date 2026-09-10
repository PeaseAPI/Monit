<?php

namespace Tests\Feature;

use App\Support\WebhookSignature;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * 第十轮：WebhookSignature 验签基座回归
 * 覆盖 Stripe 官方签名（含时间戳重放窗口）、通用 HMAC 头守门、
 * 出站 URL 私网判定（SSRF 防御）——全部离线可验证。
 */
class WebhookSignatureTest extends TestCase
{
    private const SECRET = 'whsec_test_abcdef123456';

    /** Stripe 签名：正确签名 + 容差内时间戳 → 通过 */
    public function test_stripe_signature_valid(): void
    {
        $body = '{"id":"evt_1","type":"checkout.session.completed"}';
        $ts = (string) time();
        $sig = hash_hmac('sha256', $ts.'.'.$body, self::SECRET);

        $this->assertTrue(
            WebhookSignature::verifyStripeSignature($body, "t={$ts},v1={$sig}", self::SECRET)
        );
    }

    /** Stripe 签名：多个 v1 候选中任一匹配即通过（官方滚动密钥语义） */
    public function test_stripe_signature_multiple_v1_candidates(): void
    {
        $body = '{"ok":true}';
        $ts = (string) time();
        $sig = hash_hmac('sha256', $ts.'.'.$body, self::SECRET);

        $this->assertTrue(
            WebhookSignature::verifyStripeSignature($body, "t={$ts},v1=".str_repeat('a', 64).",v1={$sig}", self::SECRET)
        );
    }

    /** Stripe 签名：时间戳超出 300s 容差 → 拒绝（重放保护） */
    public function test_stripe_signature_rejects_stale_timestamp(): void
    {
        $body = '{"id":"evt_1"}';
        $ts = (string) (time() - 301);
        $sig = hash_hmac('sha256', $ts.'.'.$body, self::SECRET);

        $this->assertFalse(
            WebhookSignature::verifyStripeSignature($body, "t={$ts},v1={$sig}", self::SECRET)
        );
    }

    /** Stripe 签名：非数字时间戳 / 缺头 / 空 secret / 坏签名 → 全部拒绝 */
    public function test_stripe_signature_fail_closed_paths(): void
    {
        $body = '{"id":"evt_1"}';
        $ts = (string) time();
        $sig = hash_hmac('sha256', $ts.'.'.$body, self::SECRET);

        $this->assertFalse(WebhookSignature::verifyStripeSignature($body, "t=notdigits,v1={$sig}", self::SECRET));
        $this->assertFalse(WebhookSignature::verifyStripeSignature($body, null, self::SECRET));
        $this->assertFalse(WebhookSignature::verifyStripeSignature($body, "t={$ts},v1={$sig}", ''));
        $this->assertFalse(WebhookSignature::verifyStripeSignature($body, "t={$ts},v1=".str_repeat('0', 64), self::SECRET));
        $this->assertFalse(WebhookSignature::verifyStripeSignature($body, 'garbage', self::SECRET));
    }

    /** 通用 HMAC 守门：hex 与 base64 双编码均接受 */
    public function test_hmac_header_accepts_hex_and_base64(): void
    {
        $body = '{"order_id":"ORD-1","status":"paid"}';

        $hex = hash_hmac('sha256', $body, self::SECRET);
        $b64 = base64_encode(hash_hmac('sha256', $body, self::SECRET, true));

        $hexReq = Request::create('/webhook', 'POST', [], [], [], ['HTTP_X_SIGNATURE' => $hex], $body);
        $this->assertTrue(WebhookSignature::verifyHmacHeader($hexReq, self::SECRET));

        $b64Req = Request::create('/webhook', 'POST', [], [], [], ['HTTP_X_SIGNATURE' => $b64], $body);
        $this->assertTrue(WebhookSignature::verifyHmacHeader($b64Req, self::SECRET));

        // 自定义头名（lemonsqueezy 用 X-Signature，其他网关可指定别名）
        $altReq = Request::create('/webhook', 'POST', [], [], [], ['HTTP_X_LEMON' => $hex], $body);
        $this->assertTrue(WebhookSignature::verifyHmacHeader($altReq, self::SECRET, 'X-Lemon'));
    }

    /** 通用 HMAC 守门：空 secret / 缺头 / 篡改 body / 错误签名 → 全部拒绝（fail-closed） */
    public function test_hmac_header_fail_closed_paths(): void
    {
        $body = '{"order_id":"ORD-1"}';
        $withSig = Request::create('/webhook', 'POST', [], [], [], ['HTTP_X_SIGNATURE' => str_repeat('0', 64)], $body);
        $noSig = Request::create('/webhook', 'POST', [], [], [], [], $body);

        $this->assertFalse(WebhookSignature::verifyHmacHeader($withSig, ''));      // 未配置
        $this->assertFalse(WebhookSignature::verifyHmacHeader($withSig, null));    // 未配置
        $this->assertFalse(WebhookSignature::verifyHmacHeader($noSig, self::SECRET)); // 缺头
        $this->assertFalse(WebhookSignature::verifyHmacHeader($withSig, self::SECRET)); // 签名不符

        // body 与签名不一致（网关 body 被代理/中间层改动后必须拒绝）
        $other = Request::create('/webhook', 'POST', [], [], [], ['HTTP_X_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET)], '{"order_id":"ORD-2"}');
        $this->assertFalse(WebhookSignature::verifyHmacHeader($other, self::SECRET));
    }

    /** 出站 URL 安全校验：非 http(s) scheme / 环回 / 云元数据 / 保留段 → 拒绝 */
    public function test_safe_http_url_rejects_dangerous_targets(): void
    {
        config(['services.webhooks.allow_private_targets' => false]);

        $this->assertFalse(WebhookSignature::isSafeHttpUrl('file:///etc/passwd'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('ftp://example.com/x'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('http://127.0.0.1:8080/hook'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('http://169.254.169.254/latest/meta-data'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('http://10.1.2.3/hook'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('http://192.168.1.1/hook'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('http://172.16.0.9/hook'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('http://[::1]/hook'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('not a url'));
        $this->assertFalse(WebhookSignature::isSafeHttpUrl('http:///no-host'));
    }

    /** 出站 URL 安全校验：公网 https/http → 放行；部署级逃生阀开启时放行私网 */
    public function test_safe_http_url_allows_public_and_escape_hatch(): void
    {
        config(['services.webhooks.allow_private_targets' => false]);
        $this->assertTrue(WebhookSignature::isSafeHttpUrl('https://hooks.example.com/ingest'));
        $this->assertTrue(WebhookSignature::isSafeHttpUrl('http://status.example.org/hook'));

        config(['services.webhooks.allow_private_targets' => true]);
        $this->assertTrue(WebhookSignature::isSafeHttpUrl('http://127.0.0.1:9000/local-debug'));
    }
}
