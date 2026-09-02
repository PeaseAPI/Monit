<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Heatmap extends Model
{
    protected $table = 'websites_heatmaps';

    protected $primaryKey = 'heatmap_id';

    public $timestamps = false;

    protected $fillable = [
        'website_id', 'path', 'name', 'snapshot_id_desktop', 'snapshot_id_tablet',
        'snapshot_id_mobile', 'desktop_size', 'tablet_size', 'mobile_size',
        'is_enabled', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'datetime' => 'datetime',
        ];
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function snapshots()
    {
        return $this->hasMany(HeatmapSnapshot::class, 'heatmap_id', 'heatmap_id');
    }

    /**
     * 三端（desktop / tablet / mobile）已采集的 snapshot_id 列表
     */
    public function snapshotIds(): array
    {
        return array_values(array_filter([
            (int) $this->snapshot_id_desktop,
            (int) $this->snapshot_id_tablet,
            (int) $this->snapshot_id_mobile,
        ]));
    }

    public function clicks()
    {
        return $this->hasMany(HeatmapSnapshotClick::class, 'snapshot_id', 'snapshot_id_desktop');
    }
}
