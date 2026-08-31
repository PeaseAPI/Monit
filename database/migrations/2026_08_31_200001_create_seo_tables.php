<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO 模块数据表
 * - seo_audits：审计主表（含三态分享字段）
 * - seo_audit_archives：历史快照（分数趋势数据源，按套餐保留期清理）
 * - notification_handlers：SEO 事件通知处理器
 * - seo_tool_uses：工具用量记录（热门榜 + 月度配额）
 * - websites / domains 追加 seo_* / monitor_* 列
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_audits')) {
            Schema::create('seo_audits', function (Blueprint $table) {
                $table->increments('seo_audit_id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedInteger('website_id')->nullable()->index();
                $table->string('uploader_key', 32)->nullable()->index();
                $table->string('url', 2048);
                $table->string('host', 256)->index();
                $table->string('type', 16)->default('single');
                $table->string('status', 16)->default('completed');
                $table->string('error', 512)->nullable();
                $table->unsignedTinyInteger('score')->default(0)->index();
                $table->unsignedSmallInteger('total_tests')->default(0);
                $table->unsignedSmallInteger('passed_tests')->default(0);
                $table->unsignedSmallInteger('major_issues')->default(0);
                $table->unsignedSmallInteger('moderate_issues')->default(0);
                $table->unsignedSmallInteger('minor_issues')->default(0);
                $table->json('category_scores')->nullable();
                $table->unsignedInteger('response_time_ms')->default(0);
                $table->unsignedInteger('page_size_bytes')->default(0);
                $table->json('results')->nullable();
                $table->text('ai_summary')->nullable();
                $table->json('ai_suggestions')->nullable();
                $table->string('privacy', 16)->default('private');
                $table->string('password')->nullable();
                $table->string('share_token', 32)->unique();
                $table->boolean('is_public_directory')->default(false)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'created_at']);
                $table->index(['website_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('seo_audit_archives')) {
            Schema::create('seo_audit_archives', function (Blueprint $table) {
                $table->increments('seo_audit_archive_id');
                $table->unsignedInteger('seo_audit_id')->index();
                $table->unsignedInteger('website_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedTinyInteger('score')->default(0);
                $table->json('snapshot')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('notification_handlers')) {
            Schema::create('notification_handlers', function (Blueprint $table) {
                $table->increments('notification_handler_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->string('name', 64);
                $table->string('type', 24);
                $table->json('settings')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->timestamp('last_sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('seo_tool_uses')) {
            Schema::create('seo_tool_uses', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('uploader_key', 32)->nullable()->index();
                $table->string('tool', 64)->index();
                $table->timestamp('created_at')->nullable();
            });
        }

        // websites 追加 SEO 调度与聚合列
        Schema::table('websites', function (Blueprint $table) {
            $table->string('seo_audit_check_interval', 16)->default('never');
            $table->boolean('seo_notifications_enabled')->default(true);
            $table->string('seo_notifications_mode', 16)->default('always');
            $table->timestamp('seo_next_audit_at')->nullable()->index();
            $table->timestamp('seo_last_audit_at')->nullable();
            $table->string('seo_sitemap_url', 512)->nullable();
            $table->string('seo_sitemap_check_interval', 16)->default('never');
            $table->string('seo_sitemap_urls_hash', 64)->nullable();
            $table->timestamp('seo_sitemap_checked_at')->nullable();
            $table->unsignedSmallInteger('seo_avg_score')->default(0);
            $table->unsignedInteger('seo_total_audits')->default(0);
        });

        // domains 追加监控列（与自定义域名用途并存）
        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('monitor_is_enabled')->default(false)->index();
            $table->date('monitor_expiration_date')->nullable();
            $table->string('monitor_registrar', 128)->nullable();
            $table->text('monitor_nameservers')->nullable();
            $table->text('monitor_ssl')->nullable();
            $table->timestamp('monitor_last_check_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_tool_uses');
        Schema::dropIfExists('notification_handlers');
        Schema::dropIfExists('seo_audit_archives');
        Schema::dropIfExists('seo_audits');

        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn([
                'seo_audit_check_interval', 'seo_notifications_enabled', 'seo_notifications_mode',
                'seo_next_audit_at', 'seo_last_audit_at', 'seo_sitemap_url',
                'seo_sitemap_check_interval', 'seo_sitemap_urls_hash', 'seo_sitemap_checked_at',
                'seo_avg_score', 'seo_total_audits',
            ]);
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'monitor_is_enabled', 'monitor_expiration_date', 'monitor_registrar',
                'monitor_nameservers', 'monitor_ssl', 'monitor_last_check_at',
            ]);
        });
    }
};
