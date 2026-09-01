<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SEO 关键词排名跟踪
 * search_engine：google / bing / baidu（SerpApi 引擎标识）
 * check_interval：never / daily / weekly / monthly（monit:seo-keywords-refresh 扫描）
 */
class SeoKeyword extends Model
{
    protected $primaryKey = 'seo_keyword_id';

    protected $fillable = [
        'user_id', 'website_id', 'keyword', 'search_engine', 'device', 'locale',
        'target_url', 'check_interval', 'is_enabled',
        'last_position', 'previous_position', 'best_position', 'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_position' => 'integer',
            'previous_position' => 'integer',
            'best_position' => 'integer',
            'last_checked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function ranks()
    {
        return $this->hasMany(SeoKeywordRank::class, 'seo_keyword_id', 'seo_keyword_id');
    }

    /**
     * 排名变化：正数 = 上升（名次提前），负数 = 下降，null = 无对比
     */
    public function getDeltaAttribute(): ?int
    {
        if ($this->last_position === null || $this->previous_position === null) {
            return null;
        }

        return $this->previous_position - $this->last_position;
    }

    /**
     * 下次应检查时间（调度扫描依据）
     */
    public function nextCheckAt(): ?\Illuminate\Support\Carbon
    {
        if (! $this->is_enabled || $this->check_interval === 'never') {
            return null;
        }

        $base = $this->last_checked_at ?? $this->created_at ?? now();

        return match ($this->check_interval) {
            'daily' => $base->copy()->addDay(),
            'weekly' => $base->copy()->addWeek(),
            'monthly' => $base->copy()->addMonth(),
            default => null,
        };
    }
}
