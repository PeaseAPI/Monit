<?php

namespace App\Services;

use App\Support\Settings;
use App\Support\WebhookSignature;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 平台 Webhook 派发服务（规格书 §6.3.1：webhooks 设置组）
 *
 * 管理员可在后台 设置 -> Webhooks 配置事件回调 URL，
 * 平台在关键业务事件发生时 POST JSON 载荷到对应 URL。
 * 派发失败仅记录日志，绝不阻塞主业务流程。
 */
class WebhookService
{
    /** 支持的事件（对应设置项 webhooks.webhook_{event}_url） */
    public const EVENTS = [
        'payment_success',
        'payment_failure',
        'user_register',
        'user_delete',
    ];

    /**
     * 派发事件到配置的回调 URL
     */
    public function dispatch(string $event, array $payload = []): void
    {
        if (! in_array($event, self::EVENTS, true)) {
            return;
        }

        $url = trim((string) Settings::get("webhooks.webhook_{$event}_url", ''));

        // 仅允许 http/https（拒绝 file://、ftp:// 等 SSRF 向量）
        if ($url === '' || ! WebhookSignature::isSafeHttpUrl($url)) {
            return;
        }

        $body = [
            'event' => $event,
            'datetime' => now()->toIso8601String(),
            'payload' => $payload,
        ];

        $headers = ['X-Monit-Event' => $event];

        // 签名（webhooks.webhooks_secret_key）：HMAC-SHA256(body)
        if ($secret = trim((string) Settings::get('webhooks.webhooks_secret_key', ''))) {
            $headers['X-Monit-Signature'] = WebhookSignature::sign($body, $secret);
        }

        try {
            // allow_redirects=false：阻断「公网 URL 30x → 内网目标」的重定向绕过
            Http::timeout(5)
                ->acceptJson()
                ->withOptions(['allow_redirects' => false])
                ->withHeaders($headers)
                ->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('webhook.dispatch_failed', [
                'event' => $event,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 便捷方法：支付成功
     */
    public function paymentSuccess(array $payload): void
    {
        $this->dispatch('payment_success', $payload);
    }

    /**
     * 便捷方法：支付失败
     */
    public function paymentFailure(array $payload): void
    {
        $this->dispatch('payment_failure', $payload);
    }

    /**
     * 便捷方法：用户注册
     */
    public function userRegister(array $payload): void
    {
        $this->dispatch('user_register', $payload);
    }

    /**
     * 便捷方法：用户删除
     */
    public function userDelete(array $payload): void
    {
        $this->dispatch('user_delete', $payload);
    }

    /**
     * 通用 URL 事件派发（webhooks.start_url / webhooks.end_url 及
     * user_update / code_redeemed / contact / domain_new / domain_update 布尔开关事件）
     *
     * 原版对标：除四个核心 URL 事件外，其余事件共享统一 URL 键
     * （webhooks.webhook_{event}_url）且带独立启用开关（webhooks.webhooks_{event}）
     */
    public function dispatchToggleable(string $event, array $payload = []): void
    {
        $enabled = Settings::get("webhooks.webhooks_{$event}");

        if ($enabled !== null && ! in_array($enabled, [true, 1, '1', 'true', 'on'], true)) {
            return;
        }

        $url = trim((string) Settings::get("webhooks.webhook_{$event}_url", ''));

        if ($url === '') {
            return;
        }

        $this->postJson($url, $event, $payload);
    }

    /** Cron 开始（webhooks.webhooks_cron_start + webhooks.start_url） */
    public function cronStart(): void
    {
        $this->dispatchToggleable('cron_start', ['started_at' => now()->toIso8601String()]);

        if ($url = trim((string) Settings::get('webhooks.start_url', ''))) {
            $this->postJson($url, 'cron_start', ['started_at' => now()->toIso8601String()]);
        }
    }

    /** Cron 结束（webhooks.webhooks_cron_end + webhooks.end_url） */
    public function cronEnd(array $results = []): void
    {
        $this->dispatchToggleable('cron_end', $results);

        if ($url = trim((string) Settings::get('webhooks.end_url', ''))) {
            $this->postJson($url, 'cron_end', $results);
        }
    }

    /** 用户资料更新（webhooks.webhooks_user_update） */
    public function userUpdate(array $payload): void
    {
        $this->dispatchToggleable('user_update', $payload);
    }

    /** 兑换码核销（webhooks.webhooks_code_redeemed） */
    public function codeRedeemed(array $payload): void
    {
        $this->dispatchToggleable('code_redeemed', $payload);
    }

    /** 联系表单（webhooks.webhooks_contact） */
    public function contact(array $payload): void
    {
        $this->dispatchToggleable('contact', $payload);
    }

    /** 新域名监控（webhooks.webhooks_domain_new） */
    public function domainNew(array $payload): void
    {
        $this->dispatchToggleable('domain_new', $payload);
    }

    /** 域名监控变更（webhooks.webhooks_domain_update） */
    public function domainUpdate(array $payload): void
    {
        $this->dispatchToggleable('domain_update', $payload);
    }

    /**
     * 裸 URL POST（start_url/end_url 场景，无事件开关包装）
     */
    protected function postJson(string $url, string $event, array $payload): void
    {
        if (! WebhookSignature::isSafeHttpUrl($url)) {
            return;
        }

        $body = [
            'event' => $event,
            'datetime' => now()->toIso8601String(),
            'payload' => $payload,
        ];

        $headers = ['X-Monit-Event' => $event];

        if ($secret = trim((string) Settings::get('webhooks.webhooks_secret_key', ''))) {
            $headers['X-Monit-Signature'] = WebhookSignature::sign($body, $secret);
        }

        try {
            // allow_redirects=false：阻断「公网 URL 30x → 内网目标」的重定向绕过
            Http::timeout(5)->acceptJson()->withOptions(['allow_redirects' => false])->withHeaders($headers)->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('webhook.dispatch_failed', ['event' => $event, 'url' => $url, 'error' => $e->getMessage()]);
        }
    }
}
