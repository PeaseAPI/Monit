<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $primaryKey = 'website_id';

    protected $fillable = [
        'user_id', 'domain_id', 'pixel_key', 'name', 'scheme', 'host', 'path',
        'tracking_type', 'is_enabled', 'bot_exclusion_is_enabled',
        'query_parameters_tracking_is_enabled', 'excluded_ips',
        'events_children_is_enabled', 'sessions_replays_is_enabled',
        'websites_heatmaps_is_enabled', 'ip_tracking_is_enabled',
        'current_month_sessions_events', 'current_month_events_children',
        'current_month_sessions_replays', 'last_24_hours_pageviews',
        'last_7_days_pageviews', 'timezone', 'email_reports_is_enabled',
        'email_reports_last_date', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'bot_exclusion_is_enabled' => 'boolean',
            'query_parameters_tracking_is_enabled' => 'boolean',
            'events_children_is_enabled' => 'boolean',
            'sessions_replays_is_enabled' => 'boolean',
            'websites_heatmaps_is_enabled' => 'boolean',
            'ip_tracking_is_enabled' => 'boolean',
            'email_reports_is_enabled' => 'boolean',
            'settings' => 'array',
            'email_reports_last_date' => 'datetime',
        ];
    }

    /* ---------------------------------------------------------------------
     | 关系
     --------------------------------------------------------------------- */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function visitors()
    {
        return $this->hasMany(WebsiteVisitor::class, 'website_id', 'website_id');
    }

    public function sessions()
    {
        return $this->hasMany(VisitorSession::class, 'website_id', 'website_id');
    }

    public function events()
    {
        return $this->hasMany(SessionEvent::class, 'website_id', 'website_id');
    }

    public function lightweightEvents()
    {
        return $this->hasMany(LightweightEvent::class, 'website_id', 'website_id');
    }

    public function goals()
    {
        return $this->hasMany(WebsiteGoal::class, 'website_id', 'website_id');
    }

    public function outboundClicks()
    {
        return $this->hasMany(OutboundClick::class, 'website_id', 'website_id');
    }

    /* ---------------------------------------------------------------------
     | 辅助方法
     --------------------------------------------------------------------- */

    /**
     * 是否为轻量跟踪模式
     */
    public function isLightweight(): bool
    {
        return $this->tracking_type === 'lightweight';
    }

    /**
     * 判断主机名是否匹配（host 存储时已去 www. 前缀）
     */
    public function matchesHost(string $host): bool
    {
        $host = strtolower(preg_replace('/^www\./', '', trim($host)));

        return $host === strtolower($this->host);
    }

    /**
     * 获取排除 IP 列表（逗号分隔 -> 数组）
     */
    public function excludedIpsList(): array
    {
        if (! $this->excluded_ips) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->excluded_ips))));
    }
}
