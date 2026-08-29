<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeatmapSnapshotClick extends Model
{
    protected $table = 'heatmap_snapshot_clicks';

    protected $primaryKey = 'click_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'snapshot_id', 'x_normalized', 'y_normalized',
        'count', 'expiration_date', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'x_normalized' => 'float',
            'y_normalized' => 'float',
            'count' => 'integer',
            'expiration_date' => 'date',
            'datetime' => 'datetime',
        ];
    }

    public function snapshot()
    {
        return $this->belongsTo(HeatmapSnapshot::class, 'snapshot_id', 'snapshot_id');
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}