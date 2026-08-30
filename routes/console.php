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

// 每分钟：Push Campaign 发送（插件 push-notifications 启用时，规格书 §13.1）
Schedule::command('monit:push-notifications-campaigns')->everyMinute();

// 每小时：会话回放 S3 Offload（插件 offload 启用时，规格书 §13.1）
Schedule::command('monit:websites-replays-offload')->hourly();

// 每小时：清理过期/太短会话回放（规格书 §13.1）
Schedule::command('monit:websites-replays-cleanup')->hourly();
