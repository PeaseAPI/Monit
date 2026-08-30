<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationCampaign extends Model
{
    protected $table = 'push_notifications_campaigns';

    protected $primaryKey = 'campaign_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'name', 'title', 'description', 'url', 'icon',
        'is_enabled', 'is_sent', 'sent_datetime', 'total_sent', 'total_failed',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_sent' => 'boolean',
            'sent_datetime' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
