<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 给 sessions_replays 添加 data LONGBLOB 列
     * 存储压缩后的 rrweb 事件 JSON（gzencode），与 heatmaps_snapshots.data 模式一致。
     * 修复：原设计仅依赖 Cache 存储回放事件，Cache 过期/清除/容量不足时回放数据丢失；
     * 现改为 DB 持久化（LONGBLOB 可存 4GB），Cache 仍保留作为 offload 命令的中间缓冲。
     */
    public function up(): void
    {
        // Schema::table 不支持 LONGBLOB，用原生 SQL
        DB::statement('ALTER TABLE sessions_replays ADD COLUMN data LONGBLOB NULL AFTER size');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE sessions_replays DROP COLUMN data');
    }
};

