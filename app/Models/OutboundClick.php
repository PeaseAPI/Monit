<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * -- 聚合查询别名（selectRaw）：
 *
 * @property string|null $k
 * @property int $total
 */
class OutboundClick extends Model
{
    protected $primaryKey = 'outbound_click_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'event_id', 'visitor_id', 'host', 'path', 'title', 'datetime',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
