<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $primaryKey = 'domain_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'scheme', 'host', 'type', 'is_enabled', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer', // 0=用户自定义域名 / 1=平台主域名（§3.1）
            'is_enabled' => 'boolean',
            'datetime' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'domain_id', 'domain_id');
    }
}