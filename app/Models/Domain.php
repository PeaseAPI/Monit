<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $primaryKey = 'domain_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'scheme', 'host', 'type', 'is_enabled', 'datetime',
        // SEO 模块：域名监控（whois 到期 / registrar / NS / SSL）
        'monitor_is_enabled', 'monitor_expiration_date', 'monitor_registrar',
        'monitor_nameservers', 'monitor_ssl', 'monitor_last_check_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'integer', // 0=用户自定义域名 / 1=平台主域名（§3.1）
            'is_enabled' => 'boolean',
            'datetime' => 'datetime',
            // SEO 模块
            'monitor_is_enabled' => 'boolean',
            'monitor_expiration_date' => 'date',
            'monitor_last_check_at' => 'datetime',
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