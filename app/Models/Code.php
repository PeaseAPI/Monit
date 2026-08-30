<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    protected $table = 'codes';

    protected $primaryKey = 'code_id';

    public $timestamps = false;

    protected $fillable = [
        'name', 'code', 'type', 'plan_id', 'days', 'discount',
        'max_redemptions', 'redeemed', 'date_start', 'date_end', 'is_enabled', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'discount' => 'decimal:2',
            'days' => 'integer',
            'max_redemptions' => 'integer',
            'redeemed' => 'integer',
            'date_start' => 'datetime',
            'date_end' => 'datetime',
            'datetime' => 'datetime',
        ];
    }

    public function redeemedCodes()
    {
        return $this->hasMany(RedeemedCode::class, 'code_id', 'code_id');
    }

    /**
     * 兑换校验（规格 §10.3）：启用 / 有效期 / 总次数上限 / 单用户一次
     * 返回 null=可兑换；否则返回语言键
     */
    public function redemptionIssue(?User $user): ?string
    {
        if (! $this->is_enabled) {
            return 'account.invalid_code';
        }

        $now = now();

        if ($this->date_start && $now->lt($this->date_start)) {
            return 'account.code_not_yet_active';
        }

        if ($this->date_end && $now->gt($this->date_end)) {
            return 'account.code_expired';
        }

        // max_redemptions：null/0 = 不限
        if ($this->max_redemptions && $this->redeemed >= $this->max_redemptions) {
            return 'account.code_fully_redeemed';
        }

        if ($user && $this->redeemedCodes()->where('user_id', $user->user_id)->exists()) {
            return 'account.code_already_redeemed';
        }

        return null;
    }

    /**
     * 记录一次兑换：redeemed_codes 关联 + codes.redeemed 计数（§3.1 双轨）
     */
    public function recordRedemption(User $user): void
    {
        RedeemedCode::create([
            'code_id' => $this->code_id,
            'user_id' => $user->user_id,
            'datetime' => now(),
        ]);

        $this->increment('redeemed');
    }

    /**
     * 将套餐类兑换码应用到用户（plan / days）
     * 同套餐未到期时叠加时长（原系统续期语义）
     */
    public function applyToUser(User $user): void
    {
        if ($this->type === 'plan' && $this->plan_id) {
            $expiry = $user->plan_expiration_date;
            $stackable = $expiry && $expiry->isFuture() && $user->plan_id === $this->plan_id;

            $user->forceFill([
                'plan_id' => $this->plan_id,
                'plan_expiration_date' => $this->days > 0
                    ? ($stackable ? $expiry : now())->addDays($this->days)
                    : null,
                'plan_expiry_reminder' => false,
            ])->save();
        }
    }
}