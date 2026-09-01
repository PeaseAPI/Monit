<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoKeyword;
use App\Services\Seo\RankTracker;
use App\Support\Settings;
use Illuminate\Console\Command;
use Throwable;

/**
 * 关键词排名定时刷新：扫描 check_interval 到期的关键词并查询 SERP
 * 前置：后台 seo.audits_is_enabled 总开关 + seo.serpapi_api_key 已配置
 */
class SeoKeywordsRefresh extends Command
{
    protected $signature = 'monit:seo-keywords-refresh';

    protected $description = '扫描到期的关键词并刷新 SERP 排名快照';

    protected int $checked = 0;

    protected int $failed = 0;

    public function handle(RankTracker $tracker): int
    {
        if (! filter_var(Settings::get('seo.audits_is_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('SEO 模块已停用（seo.audits_is_enabled），跳过。');

            return self::SUCCESS;
        }

        if (! RankTracker::configured()) {
            $this->info('未配置 seo.serpapi_api_key，跳过自动排名刷新。');

            return self::SUCCESS;
        }

        SeoKeyword::query()
            ->where('is_enabled', true)
            ->whereIn('check_interval', ['daily', 'weekly', 'monthly'])
            ->with('website')
            ->chunkById(50, function ($keywords) use ($tracker) {
                foreach ($keywords as $keyword) {
                    $next = $keyword->nextCheckAt();

                    if ($next !== null && $next->isFuture()) {
                        continue;
                    }

                    try {
                        $tracker->check($keyword);
                        $this->checked++;
                    } catch (Throwable $e) {
                        report($e);
                        $this->failed++;
                    }
                }
            });

        $this->info("关键词排名刷新：{$this->checked} 成功，{$this->failed} 失败。");

        return self::SUCCESS;
    }
}
