<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * websites.stats_month：月度用量计数（current_month_*）的重置标记。
 *
 * 背景：WebsiteMaintenanceCommand 每小时按 stats_month 判断跨月重置，但该列
 * 此前不存在导致命令每小时 SQL 报错（Unknown column 'stats_month'）——
 * current_month_* 计数由 PixelTracker 递增、PlanLimitService 用于配额判断，
 * 若永不重置，用户套餐月度配额会被历史月份用量永久占用。
 *
 * 回填策略：存量行填当前月（保持现有计数，跨月时才归零），避免迁移瞬间
 * 清零在用量中的计数。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->string('stats_month', 7)->nullable()->after('settings');
        });

        DB::table('websites')->whereNull('stats_month')->update([
            'stats_month' => now()->format('Y-m'),
        ]);
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn('stats_month');
        });
    }
};
