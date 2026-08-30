<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageOptimizerStat extends Model
{
    protected $table = 'image_optimizer_stats';

    protected $primaryKey = 'stat_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'file_type', 'original_size', 'optimized_size', 'datetime',
    ];

    protected function casts(): array
    {
        return [
            'datetime' => 'datetime',
        ];
    }
}
