<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'type', 'name', 'email', 'password', 'billing', 'api_key',
        'email_activation_code', 'lost_password_code', 'is_newsletter_subscribed',
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

    /* ---------------------------------------------------------------------
     | 辅助方法
     --------------------------------------------------------------------- */

    public function isAdmin(): bool
    {
        return $this->type === 1;
    }

    /**
     * 获取用户当前套餐设置（plan_settings 融合套餐表 settings）
     */
    public function getPlanSettings(): array
    {
        if ($this->plan_id === 'custom' && $this->plan_settings) {
            return $this->plan_settings;
        }

        $plan = Plan::find($this->plan_id);

        return $plan?->settings ?? config('monit.plan_defaults');
    }
}

