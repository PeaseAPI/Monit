<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $primaryKey = 'tax_id';

    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'value', 'value_type', 'type', 'billing_type',
        'countries', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'countries' => 'array',
            'datetime' => 'datetime',
        ];
    }
}