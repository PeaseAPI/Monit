<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 修复 heatmap_snapshot_clicks / heatmap_snapshot_scrolls 主键列名
 *
 * 基础迁移 2026_08_28_000014 使用 click_id / scroll_id，
 * 但生产环境旧表主键为 id（Laravel 默认 $table->id()），
 * 导致模型 $primaryKey = 'click_id'/'scroll_id' 与实际列不匹配，
 * Eloquent 查找/删除/更新全部失败。
 *
 * 本迁移将生产环境 id 列重命名为 click_id / scroll_id，与规格书及模型对齐。
 */
return new class extends Migration
{
    public function up(): void
    {
        // heatmap_snapshot_clicks: id → click_id
        if (Schema::hasColumn('heatmap_snapshot_clicks', 'id')
            && ! Schema::hasColumn('heatmap_snapshot_clicks', 'click_id')) {
            DB::statement('ALTER TABLE heatmap_snapshot_clicks CHANGE `id` `click_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        // heatmap_snapshot_scrolls: id → scroll_id
        if (Schema::hasColumn('heatmap_snapshot_scrolls', 'id')
            && ! Schema::hasColumn('heatmap_snapshot_scrolls', 'scroll_id')) {
            DB::statement('ALTER TABLE heatmap_snapshot_scrolls CHANGE `id` `scroll_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('heatmap_snapshot_clicks', 'click_id')
            && ! Schema::hasColumn('heatmap_snapshot_clicks', 'id')) {
            DB::statement('ALTER TABLE heatmap_snapshot_clicks CHANGE `click_id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasColumn('heatmap_snapshot_scrolls', 'scroll_id')
            && ! Schema::hasColumn('heatmap_snapshot_scrolls', 'id')) {
            DB::statement('ALTER TABLE heatmap_snapshot_scrolls CHANGE `scroll_id` `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }
};
