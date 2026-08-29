<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeatmapSnapshot extends Model
{
    protected $table = 'heatmaps_snapshots';

    protected $primaryKey = 'snapshot_id';

    public $timestamps = false;

    protected $fillable = [
        'heatmap_id', 'website_id', 'type', 'data', 'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function heatmap()
    {
        return $this->belongsTo(Heatmap::class, 'heatmap_id', 'heatmap_id');
    }
}