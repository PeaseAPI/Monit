<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationSubscriber extends Model
{
    protected $table = 'push_notifications_subscribers';

    protected $primaryKey = 'subscriber_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'user_id', 'endpoint', 'keys_p256dh', 'keys_auth',
        'ip', 'country_code', 'city', 'subscriber_datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
