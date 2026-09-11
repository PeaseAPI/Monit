<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 关键词排名快照（position 为 null = 未在结果页找到目标站）
 *
 * @property int $seo_keyword_rank_id
 * @property int $seo_keyword_id
 * @property int|null $position
 * @property string|null $url_found
 * @property string|null $source
 * @property Carbon|null $checked_at
 * @property Carbon|null $created_at
 */
class SeoKeywordRank extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'seo_keyword_rank_id';

    protected $fillable = [
        'seo_keyword_id', 'position', 'url_found', 'source', 'checked_at', 'created_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'checked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SeoKeyword, $this>
     */
    public function keyword()
    {
        return $this->belongsTo(SeoKeyword::class, 'seo_keyword_id', 'seo_keyword_id');
    }
}
