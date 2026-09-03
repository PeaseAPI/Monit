<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    /**
     * M23 性能优化：用户（套餐/状态）变化时联动失效名下站点的 pixel 查询缓存
     * 关联：PixelTrackController（pixel.website.{key} 缓存）/ Website::booted
     */
    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            foreach ($user->websites()->pluck('pixel_key') as $pixelKey) {
                Cache::forget('pixel.website.'.$pixelKey);
            }
        });
    }

    protected $fillable = [
        'type', 'name', 'email', 'password', 'billing', 'api_key',
        'email_activation_code', 'lost_password_code', 'lost_password_sent_at', 'is_newsletter_subscribed',
        'phone', 'phone_verified_at',
        'plan_id', 'plan_expiration_date', 'plan_settings', 'plan_trial_done',
        'plan_expiry_reminder', 'user_deletion_reminder', 'referral_key', 'referred_by',
        'referred_by_has_converted', 'payment_subscription_id', 'payment_processor',
        'payment_total_amount', 'payment_currency', 'language', 'timezone', 'status',
        'source', 'ip', 'latitude', 'longitude', 'continent_code', 'country', 'city_name',
        'device_type', 'os_name', 'browser_name', 'last_activity', 'total_logins',
        'preferences', 'avatar', 'anti_phishing_code', 'twofa_token', 'twofa_is_enabled',
    ];

    protected $hidden = [
        'password', 'remember_token', 'api_key', 'email_activation_code',
        'lost_password_code', 'twofa_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'lost_password_sent_at' => 'datetime',
            'password' => 'hashed',
            'billing' => 'array',
            'plan_settings' => 'array',
            'plan_expiration_date' => 'datetime',
            'plan_trial_done' => 'boolean',
            'plan_expiry_reminder' => 'boolean',
            'user_deletion_reminder' => 'boolean',
            'is_newsletter_subscribed' => 'boolean',
            'referred_by_has_converted' => 'boolean',
            'twofa_is_enabled' => 'boolean',
            'preferences' => 'array',
            'last_activity' => 'datetime',
        ];
    }

    /* ---------------------------------------------------------------------
     | 关系
     --------------------------------------------------------------------- */

    public function websites()
    {
        return $this->hasMany(Website::class, 'user_id', 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id', 'user_id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class, 'user_id', 'user_id');
    }

    public function teams()
    {
        return $this->hasMany(Team::class, 'user_id', 'user_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class, 'user_id', 'user_id');
    }

    public function internalNotifications()
    {
        return $this->hasMany(InternalNotification::class, 'user_id', 'user_id');
    }

    public function accountLogs()
    {
        return $this->hasMany(AccountLog::class, 'user_id', 'user_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by', 'user_id');
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by', 'user_id');
    }

    public function dashboardViews()
    {
        return $this->hasMany(DashboardView::class, 'user_id', 'user_id');
    }

    public function seoAudits()
    {
        return $this->hasMany(SeoAudit::class, 'user_id', 'user_id');
    }

    public function notificationHandlers()
    {
        return $this->hasMany(NotificationHandler::class, 'user_id', 'user_id');
    }

    public function annotations()
    {
        return $this->hasMany(Annotation::class, 'user_id', 'user_id');
    }

    public function redeemedCodes()
    {
        return $this->hasMany(RedeemedCode::class, 'user_id', 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->type === 1;
    }

    /**
     * 用户是否为活跃状态
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * 获取用户当前套餐设置（plan_settings 融合套餐表 settings）
     *
     * 优先级（对标 monit.cn /admin/user-update 自定义限额语义）：
     * 1) plan_id=custom 且有用户级 plan_settings → 直接使用
     * 2) 用户级 plan_settings 非空 → 逐键覆盖套餐默认（管理员微调单个用户限额）
     * 3) 套餐表 settings → config 兜底
     */
    public function getPlanSettings(): array
    {
        $userSettings = $this->plan_settings;

        if ($this->plan_id === 'custom' && $userSettings) {
            return $userSettings;
        }

        $plan = Plan::find($this->plan_id);
        $base = $plan?->settings ?? config('monit.plan_defaults');

        if ($userSettings) {
            return array_merge($base, $userSettings);
        }

        return $base;
    }

    /**
     * 生成新的 API Token
     */
    public function generateApiToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->update(['api_key' => $token]);

        return $token;
    }

    /**
     * 验证 API Token（Bearer Token 方式）
     */
    public function validateApiToken(string $token): bool
    {
        return $this->api_key === $token;
    }
}
