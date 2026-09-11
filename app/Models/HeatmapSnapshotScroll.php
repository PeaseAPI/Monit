<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeatmapSnapshotScroll extends Model
{
    protected $table = 'heatmap_snapshot_scrolls';

    protected $primaryKey = 'scroll_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'snapshot_id', 'event_uuid_binary',
        'max_scroll', 'expiration_date', 'last_datetime', 'datetime',
    ];

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return [
            'max_scroll' => 'integer',
            'expiration_date' => 'date',
            'last_datetime' => 'datetime',
            'datetime' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<HeatmapSnapshot, $this>
     */
    public function snapshot()
    {
        return $this->belongsTo(HeatmapSnapshot::class, 'snapshot_id', 'snapshot_id');
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
