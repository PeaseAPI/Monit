<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SEO 审计报告
 * privacy 三态：public（链接直达）/ private（仅作者）/ password（凭密码访问）
 */
class SeoAudit extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'seo_audit_id';

    protected $fillable = [
        'user_id', 'website_id', 'uploader_key', 'url', 'host', 'type', 'status', 'error',
        'score', 'total_tests', 'passed_tests', 'major_issues', 'moderate_issues', 'minor_issues',
        'category_scores', 'response_time_ms', 'page_size_bytes', 'results',
        'ai_summary', 'ai_suggestions', 'privacy', 'password', 'share_token', 'is_public_directory',
    ];

    protected function casts(): array
    {
        return [
            'category_scores' => 'array',
            'results' => 'array',
            'ai_suggestions' => 'array',
            'is_public_directory' => 'boolean',
            'score' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function archives()
    {
        return $this->hasMany(SeoAuditArchive::class, 'seo_audit_id', 'seo_audit_id');
    }

    /**
     * 分数带：good > 79 / decent 50-79 / poor < 50
     */
    public static function bandOf(int $score): string
    {
        return $score > 79 ? 'good' : ($score >= 50 ? 'decent' : 'poor');
    }

    public function getBandAttribute(): string
    {
        return static::bandOf((int) $this->score);
    }

    /**
     * 按类别取测试结果（报告页四类仪表数据源）
     */
    public function resultsByCategory(): array
    {
        $grouped = [];

        foreach ((array) ($this->results ?? []) as $key => $row) {
            $grouped[$row['category'] ?? 'misc'][$key] = $row;
        }

        return $grouped;
    }
}
