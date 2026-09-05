<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // websites_heatmaps
        Schema::create('websites_heatmaps', function (Blueprint $table) {
            $table->increments('heatmap_id');
            $table->unsignedInteger('website_id');
            $table->unsignedInteger('user_id')->nullable()->after('heatmap_id');
            $table->string('path', 2048);
            $table->string('name', 256);
            $table->unsignedInteger('snapshot_id_desktop')->nullable();
            $table->unsignedInteger('snapshot_id_tablet')->nullable();
            $table->unsignedInteger('snapshot_id_mobile')->nullable();
            $table->string('desktop_size', 16)->nullable();
            $table->string('tablet_size', 16)->nullable();
            $table->string('mobile_size', 16)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->dateTime('datetime');
            $table->dateTime('last_datetime')->nullable()->after('datetime');
        });

        // heatmaps_snapshots
        Schema::create('heatmaps_snapshots', function (Blueprint $table) {
            $table->increments('snapshot_id');
            $table->unsignedInteger('heatmap_id');
            $table->unsignedInteger('website_id');
            $table->enum('type', ['desktop', 'tablet', 'mobile'])->default('desktop');
            // gzencode 压缩后的 rrweb DOM 快照（二进制，规格 §4.4）：utf8mb4 文本列存二进制
            // 会被 MySQL 1366 拒绝；BLOB 上限 64KB 不够整页 DOM，升为 LONGBLOB（up() 末尾 ALTER）
            $table->binary('data');
            $table->dateTime('date');
        });

        // heatmap_snapshot_clicks
        Schema::create('heatmap_snapshot_clicks', function (Blueprint $table) {
            $table->increments('click_id');
            $table->unsignedInteger('website_id');
            $table->unsignedInteger('snapshot_id');
            $table->decimal('x_normalized', 5, 2);
            $table->decimal('y_normalized', 5, 2);
            $table->unsignedTinyInteger('count')->default(1);
            $table->date('expiration_date');
            $table->dateTime('datetime');
        });

        // heatmap_snapshot_scrolls
        Schema::create('heatmap_snapshot_scrolls', function (Blueprint $table) {
            $table->increments('scroll_id');
            $table->unsignedInteger('website_id');
            $table->unsignedInteger('snapshot_id');
            $table->binary('event_uuid_binary', 16)->unique();
            $table->unsignedTinyInteger('max_scroll');
            $table->date('expiration_date');
            $table->dateTime('last_datetime');
            $table->dateTime('datetime');
        });

        // BLOB(64KB) → LONGBLOB(4GB)：整页 rrweb DOM 快照（gzip 后仍可达数百 KB）
        DB::statement('ALTER TABLE heatmaps_snapshots MODIFY data LONGBLOB NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('heatmap_snapshot_scrolls');
        Schema::dropIfExists('heatmap_snapshot_clicks');
        Schema::dropIfExists('heatmaps_snapshots');
        Schema::dropIfExists('websites_heatmaps');
    }
};
