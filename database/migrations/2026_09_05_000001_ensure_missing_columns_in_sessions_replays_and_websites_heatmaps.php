<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 确保 sessions_replays 和 websites_heatmaps 表包含完整列
 *
 * 旧安装：基础建表迁移已运行但缺少 user_id/events/size/... 等列，
 *         本迁移检测并补齐（替换已删除的 2026_09_04_000001/000002 补丁迁移）
 * 新安装：基础建表迁移已包含所有列，本迁移为空操作（hasColumn 检查跳过）
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── sessions_replays ──
        Schema::table('sessions_replays', function (Blueprint $table) {
            if (! Schema::hasColumn('sessions_replays', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('replay_id');
            }
            if (! Schema::hasColumn('sessions_replays', 'events')) {
                $table->unsignedInteger('events')->nullable()->after('website_id');
            }
            if (! Schema::hasColumn('sessions_replays', 'size')) {
                $table->unsignedBigInteger('size')->default(0)->after('events');
            }
            if (! Schema::hasColumn('sessions_replays', 'is_too_short')) {
                $table->boolean('is_too_short')->default(true)->after('is_offloaded');
            }
            if (! Schema::hasColumn('sessions_replays', 'last_datetime')) {
                $table->timestamp('last_datetime')->nullable()->after('datetime');
            }
            if (! Schema::hasColumn('sessions_replays', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('last_datetime');
            }
        });

        // ── websites_heatmaps ──
        Schema::table('websites_heatmaps', function (Blueprint $table) {
            if (! Schema::hasColumn('websites_heatmaps', 'user_id')) {
                $table->unsignedInteger('user_id')->nullable()->after('heatmap_id');
            }
            if (! Schema::hasColumn('websites_heatmaps', 'last_datetime')) {
                $table->dateTime('last_datetime')->nullable()->after('datetime');
            }
        });
    }

    public function down(): void
    {
        // 不做回退——这些列是功能必需的
    }
};
