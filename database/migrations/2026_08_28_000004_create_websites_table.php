<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: websites 网站表
 * 依据规格书 §3.2 websites 表（PixelTrack.php INSERT + Cron.php UPDATE 反推）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id('website_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            // 自定义域名（Phase 3，预留）
            $table->unsignedBigInteger('domain_id')->nullable();
            $table->string('pixel_key', 64)->unique();
            $table->string('name', 256);
            // host 去除 www. 前缀后存储
            $table->string('scheme', 8)->default('https');
            $table->string('host', 256);
            $table->string('path', 256)->default('');
            // advanced=多表完整数据, lightweight=单表轻量
            $table->string('tracking_type', 16)->default('advanced')->index();
            // 功能开关
            $table->boolean('is_enabled')->default(true)->index();
            $table->boolean('bot_exclusion_is_enabled')->default(true);
            $table->boolean('query_parameters_tracking_is_enabled')->default(false);
            $table->text('excluded_ips')->nullable();
            $table->boolean('events_children_is_enabled')->default(true);
            $table->boolean('sessions_replays_is_enabled')->default(false);
            $table->boolean('websites_heatmaps_is_enabled')->default(false);
            $table->boolean('ip_tracking_is_enabled')->default(false);
            // 当月用量计数（cron 月度重置）
            $table->unsignedBigInteger('current_month_sessions_events')->default(0);
            $table->unsignedBigInteger('current_month_events_children')->default(0);
            $table->unsignedBigInteger('current_month_sessions_replays')->default(0);
            $table->unsignedBigInteger('last_24_hours_pageviews')->default(0);
            $table->unsignedBigInteger('last_7_days_pageviews')->default(0);
            // 限额通知标记
            $table->boolean('plan_sessions_events_limit_notice')->default(false);
            $table->boolean('plan_events_children_limit_notice')->default(false);
            $table->boolean('plan_sessions_replays_limit_notice')->default(false);
            $table->string('timezone', 64)->default('Asia/Shanghai');
            $table->boolean('email_reports_is_enabled')->default(false);
            $table->timestamp('email_reports_last_date')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_enabled']);
            $table->index('host');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
