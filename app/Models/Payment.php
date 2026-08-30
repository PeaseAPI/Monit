<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'name', 'email', 'external_id', 'payment_processor', 'type',
        'frequency', 'base_amount', 'billing', 'status', 'code_id', 'discount_amount', 'taxes_amount', 'total_amount',
        'currency', 'datetime', 'last_datetime',
    ];

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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}