<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $plan_id
 * @property string $name
 * @property string|null $description
 * @property array<string, array<string, float|int>> $prices
 * @property array<string, mixed> $settings
 * @property array<string, mixed> $additional_settings
 * @property array<string, mixed> $translations
 * @property array<int, string> $taxes_ids
 * @property int $order
 * @property int $trial_days
 * @property bool $is_enabled
 *                            -- 动态挂载属性（IndexController 定价卡计算后附加）：
 * @property float $landing_price
 * @property float $landing_price_annual
 * @property string $landing_currency
 */
class Plan extends Model
{
    protected $primaryKey = 'plan_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'plan_id', 'name', 'description', 'prices', 'settings',
        'additional_settings', 'translations', 'order', 'trial_days',
        'taxes_ids', 'is_enabled',
    ];

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return [
            'prices' => 'array',
            'settings' => 'array',
            'additional_settings' => 'array',
            'translations' => 'array',
            'taxes_ids' => 'array',
            'trial_days' => 'integer',
            'order' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }
}
