<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Monit 调度任务（规格书 §13）
|--------------------------------------------------------------------------
*/

// 每小时：套餐过期降级
Schedule::command('monit:users-plan-expiration')->hourly();

// 每小时：转化追踪
Schedule::command('monit:track-conversions')->hourly();

// 每小时：网站维护（月度计数重置等）
Schedule::command('monit:website-maintenance')->hourly();

// 每日：分析数据清理
Schedule::command('monit:analytics-cleanup')->dailyAt('03:00');

// 每日：自动删除未确认用户
Schedule::command('monit:auto-delete-unconfirmed-users')->dailyAt('04:00');

// 每日：套餐到期提醒
Schedule::command('monit:users-plan-expiry-reminder')->dailyAt('09:00');

// 每日：发送邮件报告
Schedule::command('monit:send-email-reports')->dailyAt('02:00');

// 每分钟：广播邮件发送
Schedule::command('monit:process-broadcasts')->everyMinute();
