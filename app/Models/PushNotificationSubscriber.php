<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $subscriber_id
 * @property int $website_id
 * @property int $user_id
 * @property string $endpoint
 * @property string $keys_p256dh
 * @property string $keys_auth
 * @property string|null $ip
 * @property string|null $country_code
 * @property string|null $city
 * @property Carbon $subscriber_datetime
 */
class PushNotificationSubscriber extends Model
{
    protected $table = 'push_notifications_subscribers';

    protected $primaryKey = 'subscriber_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'user_id', 'endpoint', 'keys_p256dh', 'keys_auth',
        'ip', 'country_code', 'city', 'subscriber_datetime',
    ];

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
