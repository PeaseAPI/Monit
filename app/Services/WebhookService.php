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

        try {
            Http::timeout(5)
                ->acceptJson()
                ->withHeaders(['X-Monit-Event' => $event])
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
}
