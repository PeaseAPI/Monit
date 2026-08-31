<?php

namespace App\Jobs\Seo;

use App\Models\SeoAudit;
use App\Services\Ai\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SEO 审计 AI 摘要（复用平台 AI 设置，队列异步生成）
 */
class SeoAiSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly SeoAudit $audit) {}

    public function handle(): void
    {
        if ($this->audit->status !== 'completed') {
            return;
        }

        $failed = collect((array) $this->audit->results)
            ->reject(fn (array $row) => $row['passed'] ?? false)
            ->map(fn (array $row, string $key) => "{$key}（{$row['importance']}）：{$row['value']}")
            ->take(20)
            ->implode("\n");

        $prompt = <<<TEXT
        请基于以下 SEO 审计数据写一段 150 字以内的中文总结（说明整体状况、最需要优先修复的 3 个问题，不使用列表格式）：
        目标：{$this->audit->url}
        总分：{$this->audit->score}/100
        类别得分：{json_encode($this->audit->category_scores, JSON_UNESCAPED_UNICODE)}
        未通过测试：
        {$failed}
        TEXT;

        $result = AiService::chat($prompt, '你是一位资深 SEO 顾问，输出简洁专业的中文分析。');

        if ($result['ok']) {
            $this->audit->update(['ai_summary' => $result['content']]);
        } else {
            Log::warning('seo.ai_summary_failed', ['audit' => $this->audit->seo_audit_id, 'error' => $result['error']]);
        }
    }
}
