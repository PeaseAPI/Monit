<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

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

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
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
