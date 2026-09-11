<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * SEO 反链台账
 * status：pending（未验证）/ active（重验命中）/ lost（重验未找到或请求失败）
 * rel：dofollow / nofollow / unknown
 *
 * @property int $seo_backlink_id
 * @property int $user_id
 * @property int $website_id
 * @property string $source_url
 * @property string $source_host
 * @property string $target_url
 * @property string|null $anchor_text
 * @property string $rel
 * @property string $status
 * @property int|null $dr
 * @property Carbon|null $last_checked_at
 * @property Carbon|null $first_seen_at
 */
class SeoBacklink extends Model
{
    protected $primaryKey = 'seo_backlink_id';

    protected $fillable = [
        'user_id', 'website_id', 'source_url', 'source_host', 'target_url',
        'anchor_text', 'rel', 'status', 'dr', 'last_checked_at', 'first_seen_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'dr' => 'integer',
            'last_checked_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public static function normalizeHost(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return strtolower(preg_replace('/^www\./i', '', $host) ?: $host);
    }

    protected static function booted(): void
    {
        static::creating(function (self $backlink): void {
            if (empty($backlink->url_hash)) {
                $backlink->url_hash = static::hashOf($backlink->source_url, $backlink->target_url);
            }
        });
    }

    /**
     * 去重键：md5(source_url|target_url)（长 URL 无法直接建联合唯一索引）
     */
    public static function hashOf(string $sourceUrl, ?string $targetUrl): string
    {
        return md5(mb_strtolower(trim($sourceUrl)).'|'.mb_strtolower(trim((string) $targetUrl)));
    }
}
