<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalNotification extends Model
{
    protected $table = 'internal_notifications';

    protected $primaryKey = 'internal_notification_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'from_user_id', 'for_type', 'for_id', 'data',
        'is_read', 'datetime',
    ];

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'data' => 'array',
            'datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id', 'user_id');
    }
}
