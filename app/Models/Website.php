<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $website_id
 * @property int $user_id
 * @property int|null $domain_id
 * @property string $pixel_key
 * @property string|null $name
 * @property string $scheme
 * @property string $host
 * @property string|null $path
 * @property string $tracking_type
 * @property bool $is_enabled
 * @property bool $bot_exclusion_is_enabled
 * @property bool $query_parameters_tracking_is_enabled
 * @property string|null $excluded_ips
 * @property bool $events_children_is_enabled
 * @property bool $sessions_replays_is_enabled
 * @property bool $websites_heatmaps_is_enabled
 * @property bool $ip_tracking_is_enabled
 * @property int $current_month_sessions_events
 * @property int $current_month_events_children
 * @property int $current_month_sessions_replays
 * @property int $last_24_hours_pageviews
 * @property int $last_7_days_pageviews
 * @property string|null $timezone
 * @property bool $email_reports_is_enabled
 * @property Carbon|null $email_reports_last_date
 * @property array<string, mixed> $settings
 * @property bool $plan_sessions_events_limit_notice
 * @property bool $plan_events_children_limit_notice
 * @property bool $plan_sessions_replays_limit_notice
 * @property string|null $stats_month
 * @property string $seo_audit_check_interval
 * @property bool $seo_notifications_enabled
 * @property string|null $seo_notifications_mode
 * @property Carbon|null $seo_next_audit_at
 * @property Carbon|null $seo_last_audit_at
 * @property string|null $seo_sitemap_url
 * @property string $seo_sitemap_check_interval
 * @property string|null $seo_sitemap_urls_hash
 * @property Carbon|null $seo_sitemap_checked_at
 * @property float|null $seo_avg_score
 * @property int $seo_total_audits
 */
class Website extends Model
{
    protected $primaryKey = 'website_id';

    /**
     * M23 性能优化：pixel_key 查询缓存主动失效
     * 关联：PixelTrackController（写入缓存 'pixel.website.{key}'）
     */
    protected static function booted(): void
    {
        static::saved(function (Website $website): void {
            if ($website->pixel_key) {
                Cache::forget('pixel.website.'.$website->pixel_key);
            }
        });

        static::deleted(function (Website $website): void {
            if ($website->pixel_key) {
                Cache::forget('pixel.website.'.$website->pixel_key);
            }
        });
    }

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
        // M22：配额通知标志（原版 plan_*_limit_notice，规格书 §13.1）
        'plan_sessions_events_limit_notice', 'plan_events_children_limit_notice',
        'plan_sessions_replays_limit_notice', 'stats_month',
        // SEO 模块：定时复审 / 通知 / Sitemap 监控 / 聚合缓存
        'seo_audit_check_interval', 'seo_notifications_enabled', 'seo_notifications_mode',
        'seo_next_audit_at', 'seo_last_audit_at', 'seo_sitemap_url',
        'seo_sitemap_check_interval', 'seo_sitemap_urls_hash', 'seo_sitemap_checked_at',
        'seo_avg_score', 'seo_total_audits',
    ];

    /**
     * @return array<string, mixed>
     */
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
            'plan_sessions_events_limit_notice' => 'boolean',
            'plan_events_children_limit_notice' => 'boolean',
            'plan_sessions_replays_limit_notice' => 'boolean',
            'settings' => 'array',
            'email_reports_last_date' => 'datetime',
            // SEO 模块
            'seo_notifications_enabled' => 'boolean',
            'seo_next_audit_at' => 'datetime',
            'seo_last_audit_at' => 'datetime',
            'seo_sitemap_checked_at' => 'datetime',
        ];
    }

    /* ---------------------------------------------------------------------
     | 关系
     --------------------------------------------------------------------- */

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<WebsiteVisitor, $this>
     */
    public function visitors()
    {
        return $this->hasMany(WebsiteVisitor::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<VisitorSession, $this>
     */
    public function sessions()
    {
        return $this->hasMany(VisitorSession::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<SessionEvent, $this>
     */
    public function events()
    {
        return $this->hasMany(SessionEvent::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<LightweightEvent, $this>
     */
    public function lightweightEvents()
    {
        return $this->hasMany(LightweightEvent::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<WebsiteGoal, $this>
     */
    public function goals()
    {
        return $this->hasMany(WebsiteGoal::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<OutboundClick, $this>
     */
    public function outboundClicks()
    {
        return $this->hasMany(OutboundClick::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<Annotation, $this>
     */
    public function annotations()
    {
        return $this->hasMany(Annotation::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<Heatmap, $this>
     */
    public function heatmaps()
    {
        return $this->hasMany(Heatmap::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<DashboardView, $this>
     */
    public function dashboardViews()
    {
        return $this->hasMany(DashboardView::class, 'website_id', 'website_id');
    }

    /**
     * @return HasMany<Domain, $this>
     */
    public function domains()
    {
        return $this->hasMany(Domain::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<SeoAudit, $this>
     */
    public function seoAudits()
    {
        return $this->hasMany(SeoAudit::class, 'website_id', 'website_id');
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
        $host = strtolower((string) preg_replace('/^www\./', '', trim($host)));
        $registeredHost = strtolower((string) preg_replace('/^www\./', '', trim($this->host)));

        return $host === $registeredHost;
    }

    /**
     * 获取排除 IP 列表（逗号分隔 -> 数组）
     *
     * @return array<string, mixed>
     */
    /**
     * @return list<string>
     */
    public function excludedIpsList(): array
    {
        if (! $this->excluded_ips) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->excluded_ips))));
    }
}
