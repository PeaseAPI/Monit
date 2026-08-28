<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: websites_visitors 访客表 [ADV 模式]
 * 依据规格书 §3.2 websites_visitors 表（INSERT ON DUPLICATE KEY UPDATE 反推）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites_visitors', function (Blueprint $table) {
            $table->id('visitor_id');
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            // UUID 二进制（16 字节）
            $table->binary('visitor_uuid_binary', 16);
            $table->string('ip', 45)->nullable();
            $table->json('custom_parameters')->nullable();
            $table->string('continent_code', 2)->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('city_name', 128)->nullable();
            $table->string('os_name', 64)->nullable();
            $table->string('os_version', 32)->nullable();
            $table->string('browser_name', 64)->nullable();
            $table->string('browser_version', 32)->nullable();
            $table->string('browser_language', 16)->nullable();
            $table->string('browser_timezone', 64)->nullable();
            $table->string('screen_resolution', 16)->nullable();
            $table->string('device_type', 16)->default('desktop');
            $table->string('theme', 8)->nullable();
            $table->timestamp('date')->nullable();
            $table->timestamp('last_date')->nullable();
            $table->unsignedBigInteger('total_sessions')->default(0);
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->json('goals_conversions_ids')->nullable();

            // 同一网站内 UUID 唯一（upsert 依据）
            $table->unique(['website_id', 'visitor_uuid_binary']);
            $table->index(['website_id', 'last_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites_visitors');
    }
};
