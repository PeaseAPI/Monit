<?php

namespace App\Services\Seo;

use App\Mail\SeoNotificationMail;
use App\Models\Domain;
use App\Models\NotificationHandler;
use App\Models\SeoAudit;
use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * SEO 事件通知分发
 *
 * 事件：audit_refreshed / audit_failed / sitemap_changed / domain_expiring
 * 渠道：email / webhook / slack / discord / telegram / pushover / ntfy / gotify
 * changes 模式：与上次快照对比无变化不发送
 */
class NotificationDispatcher
{
    public function dispatchForAudit(SeoAudit $audit, ?Website $website): void
    {
        $user = $audit->user_id ? User::find($audit->user_id) : null;

        if ($user === null) {
            return;
        }

        $event = $audit->status === 'completed' ? 'audit_refreshed' : 'audit_failed';

        // changes 去噪：结果与上次一致则跳过
        if ($event === 'audit_refreshed' && $website && $website->seo_notifications_mode === 'changes') {
            $current = md5(json_encode($audit->results));

            $previous = $website->seoAudits()
                ->where('seo_audit_id', '!=', $audit->seo_audit_id)
                ->where('status', 'completed')
                ->orderByDesc('seo_audit_id')
                ->first();

            if ($previous && md5(json_encode($previous->results)) === $current) {
                return;
            }
        }

        $title = $audit->status === 'completed'
            ? "SEO 复审完成：{$audit->host}（{$audit->score} 分）"
            : "SEO 复审失败：{$audit->host}";

        $message = $audit->status === 'completed'
            ? "得分 {$audit->score}/100，重大问题 {$audit->major_issues} 项、中等 {$audit->moderate_issues} 项、轻微 {$audit->minor_issues} 项。"
            : ('失败原因：'.($audit->error ?: '未知'));

        $this->dispatch($user, $event, $title, $message, route('seo.audits.show', $audit->seo_audit_id));
    }

    public function dispatchForSitemap(Website $website, array $diff): void
    {
        $user = User::find($website->user_id);

        if ($user === null) {
            return;
        }

        $this->dispatch(
            $user,
            'sitemap_changed',
            "Sitemap 变更：{$website->host}",
            sprintf('新增 %d 个 URL，移除 %d 个 URL。', count($diff['added'] ?? []), count($diff['removed'] ?? [])),
            route('websites.seo', $website->website_id)
        );
    }

    public function dispatchForDomain(Domain $domain, int $daysLeft): void
    {
        $user = User::find($domain->user_id);

        if ($user === null) {
            return;
        }

        $this->dispatch(
            $user,
            'domain_expiring',
            "域名即将到期：{$domain->host}",
            "距到期还有 {$daysLeft} 天（{$domain->monitor_expiration_date}），请及时续费。",
            route('domains.index')
        );
    }

    /**
     * 分发到用户全部已启用且订阅该事件的处理器
     */
    public function dispatch(User $user, string $event, string $title, string $message, ?string $link = null): void
    {
        foreach ($user->notificationHandlers()->where('is_enabled', true)->get() as $handler) {
            if (! $handler->subscribesTo($event)) {
                continue;
            }

            try {
                $sent = $this->send($handler, $user, $title, $message, $link);
            } catch (Throwable $e) {
                report($e);
                $sent = false;
            }

            if ($sent) {
                $handler->update(['last_sent_at' => now()]);
            }
        }
    }

    protected function send(NotificationHandler $handler, User $user, string $title, string $message, ?string $link): bool
    {
        $settings = (array) $handler->settings;

        $text = "{$title}\n{$message}".($link ? "\n{$link}" : '');

        return match ($handler->type) {
            'email' => $this->sendEmail($user, $title, $message, $link),
            'webhook' => $this->postJson((string) ($settings['webhook_url'] ?? ''), [
                'event' => 'seo_notification',
                'title' => $title,
                'message' => $message,
                'link' => $link,
            ]),
            'slack' => $this->postJson((string) ($settings['webhook_url'] ?? ''), ['text' => $text]),
            'discord' => $this->postJson((string) ($settings['webhook_url'] ?? ''), ['content' => $text]),
            'telegram' => $this->sendTelegram($settings, $title, $message, $link),
            'pushover' => $this->sendPushover($settings, $title, $message, $link),
            'ntfy' => $this->sendNtfy($settings, $title, $message, $link),
            'gotify' => $this->sendGotify($settings, $title, $message, $link),
            default => false,
        };
    }

    protected function sendEmail(User $user, string $title, string $message, ?string $link): bool
    {
        Mail::to($user->email)->send(new SeoNotificationMail($title, $message, $link));

        return true;
    }

    protected function sendTelegram(array $settings, string $title, string $message, ?string $link): bool
    {
        $token = (string) ($settings['bot_token'] ?? '');

        if ($token === '') {
            return false;
        }

        return $this->postJson("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => (string) ($settings['chat_id'] ?? ''),
            'text' => "{$title}\n{$message}".($link ? "\n{$link}" : ''),
        ]);
    }

    protected function sendPushover(array $settings, string $title, string $message, ?string $link): bool
    {
        return $this->postJson('https://api.pushover.net/1/messages.json', [
            'token' => (string) ($settings['api_token'] ?? ''),
            'user' => (string) ($settings['user_key'] ?? ''),
            'title' => $title,
            'message' => $message,
            'url' => $link,
        ]);
    }

    protected function sendNtfy(array $settings, string $title, string $message, ?string $link): bool
    {
        $server = rtrim((string) ($settings['server'] ?? 'https://ntfy.sh'), '/');
        $topic = (string) ($settings['topic'] ?? '');

        if ($topic === '') {
            return false;
        }

        $response = Http::timeout(10)->withHeaders(['Title' => $title])
            ->withBody($message.($link ? "\n{$link}" : ''), 'text/plain')
            ->post("{$server}/{$topic}");

        return $response->successful();
    }

    protected function sendGotify(array $settings, string $title, string $message, ?string $link): bool
    {
        $server = rtrim((string) ($settings['server'] ?? ''), '/');
        $token = (string) ($settings['app_token'] ?? '');

        if ($server === '' || $token === '') {
            return false;
        }

        return $this->postJson("{$server}/message?token={$token}", [
            'title' => $title,
            'message' => $message.($link ? "\n{$link}" : ''),
            'priority' => 5,
        ]);
    }

    protected function postJson(string $url, array $payload): bool
    {
        if ($url === '') {
            return false;
        }

        return Http::timeout(10)->post($url, $payload)->successful();
    }
}
