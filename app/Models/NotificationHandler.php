<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SEO 事件通知处理器
 * type：email / webhook / slack / discord / telegram / pushover / ntfy / gotify
 * settings：各渠道参数（webhook_url / token / chat_id 等）+ events（订阅事件列表）
 */
class NotificationHandler extends Model
{
    public const TYPES = ['email', 'webhook', 'slack', 'discord', 'telegram', 'pushover', 'ntfy', 'gotify'];

    public const EVENTS = ['audit_refreshed', 'audit_failed', 'sitemap_changed', 'domain_expiring'];

    protected $primaryKey = 'notification_handler_id';

    protected $fillable = [
        'user_id', 'name', 'type', 'settings', 'is_enabled', 'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_enabled' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * 是否订阅了指定事件
     */
    public function subscribesTo(string $event): bool
    {
        $events = (array) ($this->settings['events'] ?? []);

        return in_array($event, $events, true);
    }
}
