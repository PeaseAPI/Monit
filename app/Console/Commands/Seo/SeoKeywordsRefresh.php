<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoKeyword;
use App\Services\Seo\RankTracker;
use App\Support\Settings;
use App\Support\Typed;
use Illuminate\Console\Command;
use Throwable;

/**
 * 关键词排名定时刷新：扫描 check_interval 到期的关键词并查询 SERP
 * 前置：后台 seo.audits_is_enabled 总开关；SerpApi 未配置时自动走
 * RankTracker 内置 Bing/百度抓取兜底（任务 #36-7），Google 引擎失败计入 failed
 */
class SeoKeywordsRefresh extends Command
{
    protected $signature = 'monit:seo-keywords-refresh';

    protected $description = '扫描到期的关键词并刷新 SERP 排名快照';

    protected int $checked = 0;

    protected int $failed = 0;

    protected int $skipped = 0;

    public function handle(RankTracker $tracker): int
    {
        if (! filter_var(Settings::get('seo.audits_is_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('SEO 模块已停用（seo.audits_is_enabled），跳过。');

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

                    // 任务 #35-7：无网站且无目标 URL 的老数据跳过（check 会抛
                    // keyword_target_host_missing，静默跳过避免错误日志刷屏）
                    if (Typed::int($keyword->website_id) <= 0 && trim(Typed::string($keyword->target_url)) === '') {
                        $this->skipped++;

                        continue;
                    }

                    try {
                        $tracker->check($keyword);
                        $this->checked++;
                    } catch (Throwable $e) {
                        report($e);
                        $this->failed++;
                    }

                    // 内置抓取模式下的礼貌节流，降低被搜索引擎反爬命中的概率
                    if (! RankTracker::configured()) {
                        usleep(600000);
                    }
                }
            });

        $this->info("关键词排名刷新：{$this->checked} 成功，{$this->failed} 失败，{$this->skipped} 跳过（无关联网站且无目标 URL）。");

        return self::SUCCESS;
    }
}
