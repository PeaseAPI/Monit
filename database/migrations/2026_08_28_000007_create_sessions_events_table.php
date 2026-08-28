<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: sessions_events 会话事件表 [ADV 模式]
 * 依据规格书 §3.2 sessions_events 表（insert_session_event() 反推）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions_events', function (Blueprint $table) {
            $table->id('event_id');
            $table->binary('event_uuid_binary', 16);
            $table->foreignId('session_id')->constrained('visitors_sessions', 'session_id')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('websites_visitors', 'visitor_id')->cascadeOnDelete();
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            // landing_page / pageview
            $table->string('type', 32)->index();
            $table->string('path', 2048);
            $table->string('title', 512)->nullable();
            $table->string('referrer_host', 256)->nullable();
            $table->string('referrer_path', 2048)->nullable();
            $table->string('utm_source', 256)->nullable();
            $table->string('utm_medium', 256)->nullable();
            $table->string('utm_campaign', 256)->nullable();
            $table->unsignedInteger('viewport_width')->nullable();
            $table->unsignedInteger('viewport_height')->nullable();
            // 跳出：landing_page=1，出现 pageview 时置 0
            $table->boolean('has_bounced')->default(true);
            $table->timestamp('date')->nullable()->index();
            // 基于 retention 计算的过期日期（cron 清理依据）
            $table->date('expiration_date')->nullable()->index();

            $table->index(['website_id', 'type', 'date']);
            $table->index(['website_id', 'date']);
            $table->index(['session_id', 'date']);
            $table->index(['visitor_id', 'date']);
            $table->index(['website_id', 'path']);
            $table->index(['website_id', 'referrer_host']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_events');
    }
};
