<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $plugin_id
 * @property string $name
 * @property bool $is_installed
 * @property bool $is_active
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $datetime
 */
class Plugin extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'plugin_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['plugin_id', 'name', 'is_installed', 'is_active', 'settings', 'datetime'];

    /**
     * @return array<string, mixed>
     */
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
