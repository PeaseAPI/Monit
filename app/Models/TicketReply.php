<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    protected $table = 'ticket_replies';

    protected $primaryKey = 'reply_id';

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'user_id', 'is_staff', 'message', 'via', 'datetime'];

    protected function casts(): array
    {
        return ['is_staff' => 'boolean', 'datetime' => 'datetime'];
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
