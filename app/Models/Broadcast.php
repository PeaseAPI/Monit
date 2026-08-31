<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $primaryKey = 'broadcast_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'title', 'content', 'type', 'status',
        'target', 'target_plan_id',
        'scheduled_at', 'sent_datetime', 'datetime',
        'total_emails', 'total_sent', 'total_failed',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_datetime' => 'datetime',
            'datetime' => 'datetime',
            'total_emails' => 'integer',
            'total_sent' => 'integer',
            'total_failed' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
