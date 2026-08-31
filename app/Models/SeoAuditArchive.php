<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SEO 审计历史快照（分数趋势折线数据源，按套餐保留期清理）
 */
class SeoAuditArchive extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'seo_audit_archive_id';

    protected $fillable = [
        'seo_audit_id', 'website_id', 'user_id', 'score', 'snapshot', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'score' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function audit()
    {
        return $this->belongsTo(SeoAudit::class, 'seo_audit_id', 'seo_audit_id');
    }
}
