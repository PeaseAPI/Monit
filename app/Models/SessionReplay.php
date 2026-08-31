<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionReplay extends Model
{
    protected $table = 'sessions_replays';

    protected $primaryKey = 'replay_id';

    public $timestamps = false;

    protected $fillable = [
        'session_id', 'visitor_id', 'website_id', 'is_offloaded', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'is_offloaded' => 'boolean',
            'datetime' => 'datetime',
        ];
    }

    public function session()
    {
        return $this->belongsTo(VisitorSession::class, 'session_id', 'session_id');
    }

    public function visitor()
    {
        return $this->belongsTo(WebsiteVisitor::class, 'visitor_id', 'visitor_id');
    }
}
