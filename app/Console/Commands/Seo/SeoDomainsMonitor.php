<?php

namespace App\Console\Commands\Seo;

use App\Models\Domain;
use App\Services\Seo\DomainMonitor;
use App\Services\Seo\NotificationDispatcher;
use App\Support\Settings;
use Illuminate\Console\Command;

/**
 * 域名监控：whois 到期复检 + 到期预警（默认 30/7/1 天三档通知，档位后台可配）
 */
class SeoDomainsMonitor extends Command
{
    protected $signature = 'monit:seo-domains-monitor';

    protected $description = '复检启用监控的域名（whois 到期 / registrar）并发送到期预警';

    public function handle(DomainMonitor $monitor): int
    {
        // 后台 seo 组开关（seo.domain_monitor_is_enabled）关闭时跳过
        if (! filter_var(Settings::get('seo.domain_monitor_is_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('域名监控已停用（seo.domain_monitor_is_enabled），跳过本次检查。');

            return self::SUCCESS;
        }

        // 预警档位：逗号分隔天数（seo.domain_monitor_alert_days，默认 30,7,1）
        $alertDays = array_map('intval', array_filter(array_map('trim', explode(',', (string) Settings::get('seo.domain_monitor_alert_days', '30,7,1')))));

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

            // 命中预警档位当天发送（跨档对齐避免重复通知；档位后台 seo.domain_monitor_alert_days 可配）
            if (in_array($daysLeft, $alertDays, true) || $daysLeft < 0) {
                app(NotificationDispatcher::class)->dispatchForDomain($domain, max(0, $daysLeft));
                $notified++;
            }
        }

        $this->info("域名监控完成：{$domains->count()} 个域名，{$notified} 条到期预警。");

        return self::SUCCESS;
    }
}
