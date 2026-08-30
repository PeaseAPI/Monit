<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Get all settings for a given group prefix
     */
    public static function getGroup(string $prefix): array
    {
        return static::where('key', 'like', "{$prefix}_%")
            ->get()
            ->mapWithKeys(fn ($s) => [str_replace("{$prefix}_", '', $s->key) => $s->value])
            ->toArray();
    }
}
