<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: outbound_clicks 出站点击表 + annotations 图表标注表 + account_logs 账户日志表
 * 依据规格书 §3.2 outbound_clicks / annotations 表 与 §3.1 account_logs 表
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_clicks', function (Blueprint $table) {
            $table->id('outbound_click_id');
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('sessions_events', 'event_id')->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained('websites_visitors', 'visitor_id')->cascadeOnDelete();
            $table->string('host', 256)->nullable();
            $table->string('path', 2048)->nullable();
            $table->string('title', 512)->nullable();
            $table->timestamp('datetime')->nullable()->index();

            $table->index(['website_id', 'host']);
        });

        Schema::create('annotations', function (Blueprint $table) {
            $table->id('annotation_id');
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('name', 256);
            $table->date('date')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'date']);
        });

        Schema::create('account_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('type', 64)->index();
            $table->string('ip', 45)->nullable();
            $table->string('device_type', 16)->nullable();
            $table->string('os_name', 64)->nullable();
            $table->string('browser_name', 64)->nullable();
            $table->string('continent_code', 2)->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('city_name', 128)->nullable();
            $table->timestamp('datetime')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_logs');
        Schema::dropIfExists('annotations');
        Schema::dropIfExists('outbound_clicks');
    }
};
