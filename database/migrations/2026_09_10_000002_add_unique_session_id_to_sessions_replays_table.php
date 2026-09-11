<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: sessions_replays.session_id 唯一索引（回放并发首建防重复）
 *
 * 背景：PixelTracker::persistReplayChunk 对同一会话「查无 → create」，
 * 并发两个 chunk 首次到达时可能同时走到 create，产生两条重复回放
 * （MySQL InnoDB 行锁对不存在行走 gap lock 天然互斥；sqlite——网页
 * 安装向导默认库——无 gap lock，竞态真实存在）。加唯一索引后由
 * persistReplayChunk 捕获 UniqueConstraintViolationException 重跑转追加。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 唯一索引前置：清理历史竞态已产生的重复行（每组保留最早一条，
        // 其余为竞态窗口内的部分合并副本，删除不影响首条的完整性）
        $duplicatedSessionIds = DB::table('sessions_replays')
            ->select('session_id')
            ->groupBy('session_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('session_id');

        foreach ($duplicatedSessionIds as $sessionId) {
            $keepId = DB::table('sessions_replays')
                ->where('session_id', $sessionId)
                ->min('replay_id');

            DB::table('sessions_replays')
                ->where('session_id', $sessionId)
                ->where('replay_id', '!=', $keepId)
                ->delete();
        }

        Schema::table('sessions_replays', function (Blueprint $table) {
            $table->unique('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('sessions_replays', function (Blueprint $table) {
            $table->dropUnique(['session_id']);
        });
    }
};
