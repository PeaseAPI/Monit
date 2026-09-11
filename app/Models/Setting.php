<?php

namespace App\Models;

use App\Support\Typed;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Get all settings for a given group prefix
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $prefix): array
    {
        return Typed::arr(static::where('key', 'like', "{$prefix}_%")
            ->get()
            ->mapWithKeys(fn ($s) => [str_replace("{$prefix}_", '', $s->key) => $s->value])
            ->toArray());
    }
}
