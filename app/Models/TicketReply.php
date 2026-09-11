<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    protected $table = 'ticket_replies';

    protected $primaryKey = 'reply_id';

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'user_id', 'is_staff', 'message', 'via', 'datetime'];

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return ['is_staff' => 'boolean', 'datetime' => 'datetime'];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
