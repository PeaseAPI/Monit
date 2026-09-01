<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LightweightEvent extends Model
{
    protected $primaryKey = 'event_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'visitor_uuid', 'type', 'path', 'referrer_host', 'referrer_path',
        'utm_source', 'utm_medium', 'utm_campaign', 'continent_code',
        'country_code', 'city_name', 'os_name', 'browser_name',
        'browser_language', 'browser_timezone', 'screen_resolution',
        'device_type', 'theme', 'date', 'expiration_date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'expiration_date' => 'date',
        ];
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
