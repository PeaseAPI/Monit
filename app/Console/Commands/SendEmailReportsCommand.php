<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailReport;
use App\Models\Website;
use Illuminate\Console\Command;

/**
 * 邮件报告 Cron
 * 规格书 §7：每日发送订阅网站的邮件报告
 */
class SendEmailReportsCommand extends Command
{
    protected $signature = 'monit:send-email-reports';

    protected $description;

    public function __construct()
    {
        parent::__construct();
        $this->description = __('console.email_reports_desc');
    }

    public function handle(): int
    {
        $yesterday = now()->subDay();
        $sent = 0;

        // last_date 为 null（从未发送）也要纳入；仅跳过昨天刚发过的，避免重复派发
        $websites = Website::where('email_reports_is_enabled', true)
            ->where('is_enabled', true)
            ->where(function ($query) use ($yesterday) {
                $query->whereNull('email_reports_last_date')
                    ->orWhere('email_reports_last_date', '<', $yesterday->format('Y-m-d'));
            })
            ->get();

        foreach ($websites as $website) {
            $user = $website->user;
            if (! $user || ! $user->isActive()) {
                continue;
            }

            SendEmailReport::dispatch($website, $user);

            $website->update(['email_reports_last_date' => $yesterday]);
            $sent++;
        }

        $this->info(__('console.email_reports_sent', ['sent' => $sent]));

        return self::SUCCESS;
    }
}
