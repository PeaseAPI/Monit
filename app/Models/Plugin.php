<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'plugin_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['plugin_id', 'name', 'is_installed', 'is_active', 'settings', 'datetime'];

    protected function casts(): array
    {
        return [
            'is_installed' => 'boolean',
            'is_active' => 'boolean',
            'settings' => 'array',
            'datetime' => 'datetime',
        ];
    }
}
