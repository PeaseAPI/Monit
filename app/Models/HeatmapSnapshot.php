<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeatmapSnapshot extends Model
{
    protected $table = 'heatmaps_snapshots';

    protected $primaryKey = 'snapshot_id';

    public $timestamps = false;

    protected $fillable = [
        'heatmap_id', 'website_id', 'type', 'data', 'date',
    ];

    /**
     * @return array<string, string|\Stringable>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Heatmap, $this>
     */
    public function heatmap()
    {
        return $this->belongsTo(Heatmap::class, 'heatmap_id', 'heatmap_id');
    }
}
