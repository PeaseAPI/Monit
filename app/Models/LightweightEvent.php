<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $event_id
 * @property int $website_id
 * @property string $visitor_uuid
 * @property string $type
 * @property string|null $path
 * @property string|null $referrer_host
 * @property string|null $referrer_path
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $continent_code
 * @property string|null $country_code
 * @property string|null $city_name
 * @property string|null $os_name
 * @property string|null $browser_name
 * @property string|null $browser_language
 * @property string|null $browser_timezone
 * @property string|null $screen_resolution
 * @property string|null $device_type
 * @property string|null $theme
 * @property Carbon $date
 * @property Carbon|null $expiration_date
 *                                        -- 聚合查询别名（selectRaw）：
 * @property string $day
 * @property int $pageviews
 * @property int $visitors
 * @property string|null $k
 * @property int $total
 * @property string $h
 * @property int $dow
 * @property int $c
 * @property string $m
 */
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

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'expiration_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
