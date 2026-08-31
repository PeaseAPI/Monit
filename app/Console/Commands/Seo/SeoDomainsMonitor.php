<?php

namespace App\Console\Commands\Seo;

use App\Models\Domain;
use App\Services\Seo\DomainMonitor;
use App\Services\Seo\NotificationDispatcher;
use Illuminate\Console\Command;

/**
 * 域名监控：whois 到期复检 + 到期预警（30/7/1 天三档通知）
 */
class SeoDomainsMonitor extends Command
{
    protected $signature = 'monit:seo-domains-monitor';

    protected $description = '复检启用监控的域名（whois 到期 / registrar）并发送到期预警';

    public function handle(DomainMonitor $monitor): int
    {
        $domains = Domain::where('monitor_is_enabled', true)->get();

        $notified = 0;

        foreach ($domains as $domain) {
            // 每日一查：当天已查跳过
            if ($domain->monitor_last_check_at && $domain->monitor_last_check_at->isToday()) {
                continue;
            }

            $daysLeft = $monitor->refresh($domain);

            if ($daysLeft === null) {
                $this->warn("{$domain->host}：whois 查询失败");

                continue;
            }

            // 30/7/1 天三档预警（跨档当天的 0 点对齐，避免重复通知）
            if (in_array($daysLeft, [30, 7, 1], true) || $daysLeft < 0) {
                app(NotificationDispatcher::class)->dispatchForDomain($domain, max(0, $daysLeft));
                $notified++;
            }
        }

        $this->info("域名监控完成：{$domains->count()} 个域名，{$notified} 条到期预警。");

        return self::SUCCESS;
    }
}
