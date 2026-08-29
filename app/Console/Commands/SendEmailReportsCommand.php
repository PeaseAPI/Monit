<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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

        $websites = Website::where('email_reports_is_enabled', true)
            ->where('is_enabled', true)
            ->where('email_reports_last_date', '<', $yesterday->format('Y-m-d'))
            ->get();

        foreach ($websites as $website) {
            $user = $website->user;
            if (!$user || !$user->isActive()) {
                continue;
            }

            $stats = $this->getStats($website, $yesterday);

            // TODO: 使用 Mail 发送
            // $this->sendReportEmail($user->email, $website->name, $stats);

            $website->update(['email_reports_last_date' => $yesterday]);
            $sent++;
        }

                $this->info(__('console.email_reports_sent', ['sent' => $sent]));

        return self::SUCCESS;
    }

    protected function getStats(Website $website, $date): array
    {
        $startTime = $date->copy()->startOfDay();
        $endTime = $date->copy()->endOfDay();

        return [
            'visitors' => $website->visitors()
                ->whereBetween('last_datetime', [$startTime, $endTime])->count(),
            'sessions' => $website->sessions()
                ->whereBetween('datetime', [$startTime, $endTime])->count(),
            'pageviews' => $website->events()
                ->where('type', 'pageview')
                ->whereBetween('datetime', [$startTime, $endTime])->count(),
        ];
    }
}