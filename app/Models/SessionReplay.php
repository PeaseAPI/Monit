<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReplay extends Model
{
    protected $table = 'sessions_replays';

    protected $primaryKey = 'replay_id';

    public $timestamps = false;

    protected $fillable = [
        'session_id', 'visitor_id', 'website_id', 'user_id', 'events', 'size', 'data',
        'is_offloaded', 'is_too_short', 'datetime', 'last_datetime', 'expiration_date',
    ];

    /**
     * data 列是 LONGBLOB（gzencode 压缩的 rrweb 事件 JSON），
     * Eloquent 会将其当作字符串属性，但读取时需要原生 SQL 来避免编码问题。
     * 该列不参与 $casts，手动用 gzencode/gzdecode 处理。
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_offloaded' => 'boolean',
            'datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VisitorSession, $this>
     */
    public function session()
    {
        return $this->belongsTo(VisitorSession::class, 'session_id', 'session_id');
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    /** @return BelongsTo<WebsiteVisitor, $this> */
    /**
     * @return BelongsTo<WebsiteVisitor, $this>
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(WebsiteVisitor::class, 'visitor_id', 'visitor_id');
    }
}
