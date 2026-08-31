<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SEO 工具用量记录（热门榜 + 月度配额统计）
 */
class SeoToolUse extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'uploader_key', 'tool', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * 指定月份用量
     */
    public static function monthlyCount(?int $userId, ?string $uploaderKey = null): int
    {
        $query = static::whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);

        return $userId !== null
            ? (clone $query)->where('user_id', $userId)->count()
            : (clone $query)->where('uploader_key', (string) $uploaderKey)->count();
    }

    /**
     * 热门工具榜
     */
    public static function topTools(int $limit = 10): array
    {
        return static::query()
            ->selectRaw('tool, count(*) as total')
            ->groupBy('tool')
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'tool')
            ->toArray();
    }
}
