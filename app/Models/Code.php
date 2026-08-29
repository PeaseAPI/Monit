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
        'max_redemptions', 'date_start', 'date_end', 'is_enabled', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'discount' => 'decimal:2',
            'date_start' => 'datetime',
            'date_end' => 'datetime',
            'datetime' => 'datetime',
        ];
    }

    public function redeemedCodes()
    {
        return $this->hasMany(RedeemedCode::class, 'code_id', 'code_id');
    }
}