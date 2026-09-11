<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUS_OPEN = 0;

    public const STATUS_ANSWERED = 1;

    public const STATUS_CLOSED = 2;

    public const CATEGORIES = ['general', 'technical', 'billing', 'feature', 'abuse'];

    public const PRIORITIES = ['low', 'normal', 'high'];

    protected $table = 'tickets';

    protected $primaryKey = 'ticket_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'email', 'subject', 'category', 'priority', 'status', 'last_reply_at', 'datetime'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['status' => 'integer', 'last_reply_at' => 'datetime', 'datetime' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<TicketReply, $this>
     */
    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id', 'ticket_id')->orderBy('reply_id');
    }

    public function statusKey(): string
    {
        return match ($this->status) {
            self::STATUS_ANSWERED => 'answered',
            self::STATUS_CLOSED => 'closed',
            default => 'open',
        };
    }

    /**
     * 工单编号展示格式（邮件主题引用）：#TK-000123
     */
    public function code(): string
    {
        return '#TK-'.str_pad((string) $this->ticket_id, 6, '0', STR_PAD_LEFT);
    }
}
