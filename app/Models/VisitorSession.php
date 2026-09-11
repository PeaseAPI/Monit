<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

/**
 * -- 聚合查询别名（selectRaw）：
 *
 * @property int $sessions
 */
class VisitorSession extends Model
{
    protected $table = 'visitors_sessions';

    protected $primaryKey = 'session_id';

    public $timestamps = false;

    protected $fillable = [
        'session_uuid_binary', 'visitor_id', 'website_id', 'date', 'total_events',
    ];

    // 安全审计周期 #15：二进制 UUID 列不能直接 JSON 序列化
    // （Malformed UTF-8 → 500），隐藏并以字符串 accessor 输出
    protected $hidden = ['session_uuid_binary'];

    protected $appends = ['session_uuid'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
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
     * @return HasMany<SessionEvent, $this>
     */
    public function events()
    {
        return $this->hasMany(SessionEvent::class, 'session_id', 'session_id');
    }

    public function getSessionUuidAttribute(): ?string
    {
        return $this->session_uuid_binary
            ? Uuid::fromBytes($this->session_uuid_binary)->toString()
            : null;
    }
}
