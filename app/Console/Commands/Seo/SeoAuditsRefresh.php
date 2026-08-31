<?php

namespace App\Console\Commands\Seo;

use App\Jobs\Seo\RunSeoAuditJob;
use App\Models\Website;
use App\Support\Settings;
use Illuminate\Console\Command;

/**
 * 定时复审：扫描 seo_next_audit_at 到期的网站并入队审计
 */
class SeoAuditsRefresh extends Command
{
    protected $signature = 'monit:seo-audits-refresh';

    protected $description = '扫描到期的 SEO 定时复审任务并入队执行';

    public function handle(): int
    {
        // 后台 seo 组总开关（seo.audits_is_enabled）关闭时跳过
        if (! filter_var(Settings::get('seo.audits_is_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('SEO 审计已停用（seo.audits_is_enabled），跳过本次复审。');

            return self::SUCCESS;
        }

        $due = Website::query()
            ->whereNotIn('seo_audit_check_interval', ['never', ''])
            ->whereNotNull('seo_next_audit_at')
            ->where('seo_next_audit_at', '<=', now())
            ->where('is_enabled', true)
            ->get();

        foreach ($due as $website) {
            RunSeoAuditJob::dispatch(
                $website->scheme.'://'.$website->host,
                $website->user_id,
                'scheduled',
                ['scheduled' => true, 'website_id' => $website->website_id],
            );
        }

        $this->info("SEO 定时复审：{$due->count()} 个网站已入队。");

        return self::SUCCESS;
    }
}
