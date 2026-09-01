<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: lightweight_events 补访客标识 [LW 模式]
 * 访客明细与旅程：轻量单表模式按 visitor_uuid 聚合访客列表与行为时间线
 * （monit.js 一直随载荷发送 visitor_uuid，此前轻量模式未落库）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lightweight_events', function (Blueprint $table) {
            $table->binary('visitor_uuid', 16)->nullable()->after('website_id');
        });

        // 复合索引（website + 访客 + 时间窗），binary(16) 用前缀长度
        DB::statement('ALTER TABLE lightweight_events ADD INDEX lightweight_events_web_visitor_date_idx (website_id, visitor_uuid(16), date)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE lightweight_events DROP INDEX lightweight_events_web_visitor_date_idx');

        Schema::table('lightweight_events', function (Blueprint $table) {
            $table->dropColumn('visitor_uuid');
        });
    }
};
