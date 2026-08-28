<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventChild extends Model
{
    protected $table = 'events_children';

    protected $primaryKey = 'event_child_id';

    public $timestamps = false;

    protected $fillable = [
        'event_id', 'session_id', 'visitor_id', 'website_id', 'type', 'data', 'count',
        'date', 'expiration_date',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'date' => 'datetime',
            'expiration_date' => 'date',
            'count' => 'integer',
        ];
    }
}
