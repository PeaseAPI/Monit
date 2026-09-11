<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $log_id
 * @property int $user_id
 * @property string $type
 * @property string $ip
 * @property string $device_type
 * @property string $os_name
 * @property string $browser_name
 * @property string $continent_code
 * @property string $country_code
 * @property string $city_name
 * @property Carbon|null $datetime
 * @property User|null $user
 */
class AccountLog extends Model
{
    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'type', 'ip', 'device_type', 'os_name', 'browser_name',
        'continent_code', 'country_code', 'city_name', 'datetime',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
