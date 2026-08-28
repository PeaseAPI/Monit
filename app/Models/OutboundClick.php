<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundClick extends Model
{
    protected $primaryKey = 'outbound_click_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'event_id', 'visitor_id', 'host', 'path', 'title', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
        ];
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
