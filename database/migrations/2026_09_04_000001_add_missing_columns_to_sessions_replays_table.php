<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 补齐 sessions_replays 表缺失列（对齐生产数据库 schema）
 * 生产库有 user_id / events / size / is_too_short / last_datetime / expiration_date，
 * 但迁移文件遗漏；回放录制/展示/offload 均依赖这些列
 */
return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::table('sessions_replays', function (Blueprint $table) {
            $columns = ['user_id', 'events', 'size', 'is_too_short', 'last_datetime', 'expiration_date'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('sessions_replays', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

