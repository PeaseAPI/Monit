<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

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

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function visitor()
    {
        return $this->belongsTo(WebsiteVisitor::class, 'visitor_id', 'visitor_id');
    }

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
