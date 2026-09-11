<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $campaign_id
 * @property int $website_id
 * @property string $name
 * @property string $title
 * @property string|null $description
 * @property string|null $url
 * @property string|null $icon
 * @property bool $is_enabled
 * @property bool $is_sent
 * @property Carbon|null $sent_datetime
 * @property int $total_sent
 * @property int $total_failed
 */
class PushNotificationCampaign extends Model
{
    protected $table = 'push_notifications_campaigns';

    protected $primaryKey = 'campaign_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'name', 'title', 'description', 'url', 'icon',
        'is_enabled', 'is_sent', 'sent_datetime', 'total_sent', 'total_failed',
    ];

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_sent' => 'boolean',
            'sent_datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
