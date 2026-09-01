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

// ---------- M22：原版 Cron 任务补齐（规格书 §13.1） ----------

// 每日 05:00：不活跃用户删除提醒（原版 users_deletion_reminder）
Schedule::command('monit:users-deletion-reminder')->dailyAt('05:00');

// 每日 06:00：自动删除不活跃用户（原版 auto_delete_inactive_users）
Schedule::command('monit:auto-delete-inactive-users')->dailyAt('06:00');

// 每日 03:30：账户日志/内部通知/日志文件清理（原版 users_logs_cleanup + internal_notifications_cleanup + logs_cleanup）
Schedule::command('monit:housekeeping-cleanup')->dailyAt('03:30');

// 每小时：站点配额超限通知（原版 websites_*_notice ×3）
Schedule::command('monit:websites-limit-notice')->hourly();

// ---------- M26：SEO 模块调度（SEO模块融合方案 §10） ----------

// 每小时：定时复审（扫描 seo_next_audit_at 到期网站，dispatch RunSeoAuditJob）
Schedule::command('monit:seo-audits-refresh')->hourly();

// 每小时：Sitemap 监控（diff → 变更通知）
Schedule::command('monit:seo-sitemaps-check')->hourly();

// 每日 06:30：域名监控复检（whois 到期 30/7/1 天预警）
Schedule::command('monit:seo-domains-monitor')->dailyAt('06:30');

// 每日 03:15：SEO 归档清理（按套餐 seo_history_retention_days）
Schedule::command('monit:seo-archives-cleanup')->dailyAt('03:15');

// 每小时：关键词排名刷新（seo.serpapi_api_key 已配置时扫描到期关键词）
Schedule::command('monit:seo-keywords-refresh')->hourly();

// 每日 05:30：反链活性重验（抓源页匹配目标站链接，标记 active/lost）
Schedule::command('monit:seo-backlinks-verify')->dailyAt('05:30');

