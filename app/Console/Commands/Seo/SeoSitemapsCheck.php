<?php

namespace App\Console\Commands\Seo;

use App\Models\Website;
use App\Services\Seo\NotificationDispatcher;
use App\Services\Seo\SitemapMonitor;
use Illuminate\Console\Command;

/**
 * Sitemap 监控：变更 diff + 新 URL 提示 + 通知
 */
class SeoSitemapsCheck extends Command
{
    protected $signature = 'monit:seo-sitemaps-check';

    protected $description = '检查启用 Sitemap 监控的网站并对比变更';

    public function handle(SitemapMonitor $monitor): int
    {
        $websites = Website::query()
            ->whereNotIn('seo_sitemap_check_interval', ['never', ''])
            ->where('is_enabled', true)
            ->get();

        $changed = 0;

        foreach ($websites as $website) {
            // 按检查间隔节流：未到期跳过
            if ($website->seo_sitemap_checked_at && $website->seo_sitemap_checked_at->gt(now()->subDays($this->intervalDays($website->seo_sitemap_check_interval)))) {
                continue;
            }

            $result = $monitor->check($website);

            if ($result['error'] !== null) {
                $this->warn("{$website->host}：{$result['error']}");

                continue;
            }

            if ($result['changed']) {
                $changed++;

                app(NotificationDispatcher::class)->dispatchForSitemap($website, $result);

                $this->info("{$website->host}：新增 ".count($result['added']).'，移除 '.count($result['removed']));
            }
        }

        $this->info("Sitemap 监控完成：{$websites->count()} 个网站，{$changed} 个发生变更。");

        return self::SUCCESS;
    }

    protected function intervalDays(string $interval): int
    {
        return match ($interval) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            default => 7,
        };
    }
}
