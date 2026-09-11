<?php

namespace App\Models;

use App\Support\Typed;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * @property int $user_id
 * @property int $type
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property array<string, mixed> $billing
 * @property array<string, mixed> $plan_settings
 * @property array<string, mixed> $preferences
 * @property string|null $email_activation_code
 * @property string|null $lost_password_code
 * @property Carbon|null $lost_password_sent_at
 * @property Carbon|null $email_verified_at
 * @property bool $is_newsletter_subscribed
 * @property string|null $phone
 * @property Carbon|null $phone_verified_at
 * @property string $plan_id
 * @property Carbon|null $plan_expiration_date
 * @property bool $plan_trial_done
 * @property bool $plan_expiry_reminder
 * @property bool $user_deletion_reminder
 * @property string|null $referral_key
 * @property string|null $referred_by
 * @property bool $referred_by_has_converted
 * @property string|null $payment_subscription_id
 * @property string|null $payment_processor
 * @property float|null $payment_total_amount
 * @property string|null $payment_currency
 * @property string|null $language
 * @property string|null $timezone
 * @property int $status
 * @property string|null $source
 * @property string|null $ip
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $continent_code
 * @property string|null $country
 * @property string|null $city_name
 * @property string|null $device_type
 * @property string|null $os_name
 * @property string|null $browser_name
 * @property Carbon|null $last_activity
 * @property int $total_logins
 * @property string|null $avatar
 * @property string|null $anti_phishing_code
 * @property string|null $twofa_token
 * @property bool $twofa_is_enabled
 * @property string|null $remember_token
 * @property Plan|null $plan
 * @property string|null $api_key_lookup
 * @property string|null $api_key_encrypted
 *                                          -- 聚合别名（selectRaw）：
 * @property int $count
 */
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
                Cache::forget('pixel.website.'.Typed::string($pixelKey));
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

    /**
     * 第十三轮：api_key 存储加密化（拖库防御）
     *
     * 明文不再落库——api_key 列恒为 NULL，真实值拆存两处：
     *  - api_key_lookup：sha256(明文)，等值索引查询用（高熵 key 不可反推）
     *  - api_key_encrypted：Crypt 加密，供账号页/账号 API 正常回显（UX 不变）
     * accessor/mutator 以同名属性接管，全部既有读写调用点零改动。
     */
    public function getApiKeyAttribute(?string $value): ?string
    {
        if ($value !== null) {
            return $value; // 迁移前的遗留行 / 回滚后的行：明文直读
        }

        if ((bool) ($this->attributes['api_key_encrypted'] ?? null)) {
            try {
                return Crypt::decryptString(Typed::string($this->attributes['api_key_encrypted']));
            } catch (Throwable) {
                return null; // APP_KEY 变更等解密失败：视为无 key（fail-closed）
            }
        }

        return null;
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = null; // 明文列废弃，防遗留写入路径回退

        if ($value === null) {
            $this->attributes['api_key_lookup'] = null;
            $this->attributes['api_key_encrypted'] = null;

            return;
        }

        $this->attributes['api_key_lookup'] = hash('sha256', $value);
        $this->attributes['api_key_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * @return array<string, string|\Stringable>
     */
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

    /**
     * @return HasMany<Website, $this>
     */
    public function websites()
    {
        return $this->hasMany(Website::class, 'user_id', 'user_id');
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<Domain, $this>
     */
    public function domains()
    {
        return $this->hasMany(Domain::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams()
    {
        return $this->hasMany(Team::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<TeamMember, $this>
     */
    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<InternalNotification, $this>
     */
    public function internalNotifications()
    {
        return $this->hasMany(InternalNotification::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<AccountLog, $this>
     */
    public function accountLogs()
    {
        return $this->hasMany(AccountLog::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by', 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by', 'user_id');
    }

    /**
     * @return HasMany<DashboardView, $this>
     */
    public function dashboardViews()
    {
        return $this->hasMany(DashboardView::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<SeoAudit, $this>
     */
    public function seoAudits()
    {
        return $this->hasMany(SeoAudit::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<NotificationHandler, $this>
     */
    public function notificationHandlers()
    {
        return $this->hasMany(NotificationHandler::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<Annotation, $this>
     */
    public function annotations()
    {
        return $this->hasMany(Annotation::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<RedeemedCode, $this>
     */
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
     *
     * @return array<string, mixed>
     */
    public function getPlanSettings(): array
    {
        // cast 'array' 对 null 列值返回 null：显式放宽为可空再窄化（§ Eloquent cast 语义）
        $userSettings = $this->plan_settings ?? null;

        if ($this->plan_id === 'custom' && $userSettings !== null && $userSettings !== []) {
            return $userSettings;
        }

        $plan = Plan::find($this->plan_id);
        /** @var array<string, mixed> $base */
        $base = $plan?->settings ?? config('monit.plan_defaults');

        if ($userSettings !== null && $userSettings !== []) {
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
        return $this->api_key !== null && hash_equals($this->api_key, $token);
    }
}
