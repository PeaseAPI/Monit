<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
