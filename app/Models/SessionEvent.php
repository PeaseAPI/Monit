<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

/**
 * @property int $event_id
 * @property string $event_uuid_binary
 * @property int $session_id
 * @property int $visitor_id
 * @property int $website_id
 * @property string $type
 * @property string|null $path
 * @property string|null $title
 * @property string|null $referrer_host
 * @property string|null $referrer_path
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property int|null $viewport_width
 * @property int|null $viewport_height
 * @property bool $has_bounced
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
class SessionEvent extends Model
{
    protected $table = 'sessions_events';

    protected $primaryKey = 'event_id';

    public $timestamps = false;

    protected $fillable = [
        'event_uuid_binary', 'session_id', 'visitor_id', 'website_id', 'type',
        'path', 'title', 'referrer_host', 'referrer_path', 'utm_source',
        'utm_medium', 'utm_campaign', 'viewport_width', 'viewport_height',
        'has_bounced', 'date', 'expiration_date',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'expiration_date' => 'date',
            'has_bounced' => 'boolean',
            'viewport_width' => 'integer',
            'viewport_height' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    /**
     * @return BelongsTo<WebsiteVisitor, $this>
     */
    public function visitor()
    {
        return $this->belongsTo(WebsiteVisitor::class, 'visitor_id', 'visitor_id');
    }

    /**
     * @return BelongsTo<VisitorSession, $this>
     */
    public function session()
    {
        return $this->belongsTo(VisitorSession::class, 'session_id', 'session_id');
    }

    public function getEventUuidAttribute(): ?string
    {
        return $this->event_uuid_binary
            ? Uuid::fromBytes($this->event_uuid_binary)->toString()
            : null;
    }
}
