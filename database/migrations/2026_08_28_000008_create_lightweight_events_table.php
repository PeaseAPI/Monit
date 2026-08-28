<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: lightweight_events 轻量事件表 [LW 模式]
 * 依据规格书 §3.2 lightweight_events 表（单表、<6kB 像素）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lightweight_events', function (Blueprint $table) {
            $table->id('event_id');
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            // landing_page / pageview
            $table->string('type', 32)->index();
            $table->string('path', 2048);
            $table->string('referrer_host', 256)->nullable();
            $table->string('referrer_path', 2048)->nullable();
            $table->string('utm_source', 256)->nullable();
            $table->string('utm_medium', 256)->nullable();
            $table->string('utm_campaign', 256)->nullable();
            // 冗余地理位置（无关联表）
            $table->string('continent_code', 2)->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('city_name', 128)->nullable();
            $table->string('os_name', 64)->nullable();
            $table->string('browser_name', 64)->nullable();
            $table->string('browser_language', 16)->nullable();
            $table->string('browser_timezone', 64)->nullable();
            $table->string('screen_resolution', 16)->nullable();
            $table->string('device_type', 16)->default('desktop');
            $table->string('theme', 8)->nullable();
            $table->timestamp('date')->nullable()->index();
            $table->date('expiration_date')->nullable()->index();

            $table->index(['website_id', 'type', 'date']);
            $table->index(['website_id', 'date']);
            $table->index(['website_id', 'path']);
            $table->index(['website_id', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lightweight_events');
    }
};
