<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class WebsiteVisitor extends Model
{
    protected $table = 'websites_visitors';

    protected $primaryKey = 'visitor_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'visitor_uuid_binary', 'ip', 'custom_parameters',
        'continent_code', 'country_code', 'city_name', 'os_name', 'os_version',
        'browser_name', 'browser_version', 'browser_language', 'browser_timezone',
        'screen_resolution', 'device_type', 'theme', 'date', 'last_date',
        'total_sessions', 'last_event_id', 'goals_conversions_ids',
    ];

    // 安全审计周期 #15：二进制 UUID 列不能直接 JSON 序列化
    // （Malformed UTF-8 → 500），隐藏并以字符串 accessor 输出
    protected $hidden = ['visitor_uuid_binary'];

    protected $appends = ['visitor_uuid'];

    protected function casts(): array
    {
        return [
            'custom_parameters' => 'array',
            'goals_conversions_ids' => 'array',
            'date' => 'datetime',
            'last_date' => 'datetime',
        ];
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function sessions()
    {
        return $this->hasMany(VisitorSession::class, 'visitor_id', 'visitor_id');
    }

    /* ---------------------------------------------------------------------
     | UUID 二进制辅助
     --------------------------------------------------------------------- */

    public static function uuidToBinary(string $uuid): string
    {
        return Uuid::fromString($uuid)->getBytes();
    }

    public static function binaryToUuid(?string $bytes): ?string
    {
        if (! $bytes) {
            return null;
        }

        return Uuid::fromBytes($bytes)->toString();
    }

    public function getVisitorUuidAttribute(): ?string
    {
        return static::binaryToUuid($this->visitor_uuid_binary);
    }
}
