<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $payment_id
 * @property int $user_id
 * @property string $name
 * @property string $email
 * @property string|null $external_id
 * @property int|null $plan_id
 * @property string $payment_processor
 * @property string $type
 * @property string $frequency
 * @property string|null $base_amount
 * @property array<string, mixed>|null $billing
 * @property int $status
 * @property int|null $code_id
 * @property string|null $discount_amount
 * @property string|null $taxes_amount
 * @property string|null $total_amount
 * @property string $currency
 * @property Carbon $datetime
 * @property Carbon|null $last_datetime
 */
class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'name', 'email', 'external_id', 'plan_id', 'payment_processor', 'type',
        'frequency', 'base_amount', 'billing', 'status', 'code_id', 'discount_amount', 'taxes_amount', 'total_amount',
        'currency', 'datetime', 'last_datetime',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'billing' => 'array',
            'base_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxes_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'datetime' => 'datetime',
            'last_datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * 关联：本次订单购买的套餐（payments.plan_id → plans.plan_id）
     * 支付成功后的套餐激活、发票套餐名展示均以本关联为准，
     * 避免激活「用户当前套餐」而非「本次购买套餐」的错配。
     * 失效位置：plan 被删除后返回 null，调用方需自行兜底（如 user->plan_id）。
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }
}
