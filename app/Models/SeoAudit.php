<?php

namespace App\Models;

use App\Support\Typed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SEO 审计报告
 * privacy 三态：public（链接直达）/ private（仅作者）/ password（凭密码访问）
 *
 * @property int $seo_audit_id
 * @property int|null $user_id
 * @property int $website_id
 * @property string|null $uploader_key
 * @property string $url
 * @property string $host
 * @property string $type
 * @property string $status
 * @property string|null $error
 * @property int|null $score
 * @property int|null $total_tests
 * @property int|null $passed_tests
 * @property int|null $major_issues
 * @property int|null $moderate_issues
 * @property int|null $minor_issues
 * @property array<string, mixed>|null $category_scores
 * @property int|null $response_time_ms
 * @property int|null $page_size_bytes
 * @property array<string, array<string, mixed>>|null $results
 * @property string|null $ai_summary
 * @property array<string, mixed>|null $ai_suggestions
 * @property string $privacy
 * @property string|null $password
 * @property string|null $share_token
 * @property bool $is_public_directory
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

    /**
     * @return array<string, string|\Stringable>
     */
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

    /**
     * @return BelongsTo<Website, $this>
     */
    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * @return HasMany<SeoAuditArchive, $this>
     */
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
     *
     * @return array<string, mixed>
     */
    public function resultsByCategory(): array
    {
        $grouped = [];

        foreach (($this->results ?? []) as $key => $row) {
            $category = Typed::string($row['category'] ?? 'misc');
            $grouped[$category][Typed::string($key)] = $row;
        }

        return $grouped;
    }
}
