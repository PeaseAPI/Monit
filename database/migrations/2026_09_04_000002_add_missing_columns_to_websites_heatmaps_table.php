<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 补齐 websites_heatmaps 表缺失列（对齐生产数据库 schema）
 * 生产库有 user_id / last_datetime，但迁移文件遗漏；
 * 自动创建热图时需要写入 user_id
 */
return new class extends Migration
{
    public function up(): void
    {
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
        Schema::table('websites_heatmaps', function (Blueprint $table) {
            if (Schema::hasColumn('websites_heatmaps', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('websites_heatmaps', 'last_datetime')) {
                $table->dropColumn('last_datetime');
            }
        });
    }
};
