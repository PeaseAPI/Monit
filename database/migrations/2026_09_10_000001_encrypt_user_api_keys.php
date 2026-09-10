<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * 第十三轮：api_key 存储加固——查找哈希 + 可逆加密，明文列废弃
 *
 * 威胁模型：SQL 注入/拖库直接读出 api_key 明文即可完全冒用账户 API；
 * 明文列废弃后：
 *  - api_key_lookup = sha256(明文)：高熵随机 key（Str::random/bin2hex(random)）
 *    不可彩虹表反推，仅供等值索引查询
 *  - api_key_encrypted = Crypt::encryptString(明文)：供账号页/账号 API
 *    正常回显（UX 零破坏），拖库方无 APP_KEY 不可解
 *  - api_key 列保留但恒置 NULL（遗留引用安全：模型 accessor 接管）
 *
 * 存量 key 无缝迁移：迁移仅转换存储形态，明文值不变——所有既有
 * API 集成不失效，用户无需任何操作。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('api_key_lookup', 64)->nullable()->after('api_key');
            $table->index('api_key_lookup');
            $table->text('api_key_encrypted')->nullable()->after('api_key_lookup');
        });

        // 逐行转换（一次性数据迁移，幂等：api_key 列已置 NULL 的行跳过）
        DB::table('users')->whereNotNull('api_key')->orderBy('user_id')->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                DB::table('users')->where('user_id', $user->user_id)->update([
                    'api_key_lookup' => hash('sha256', (string) $user->api_key),
                    'api_key_encrypted' => Crypt::encryptString((string) $user->api_key),
                    'api_key' => null,
                ]);
            }
        }, 'user_id');
    }

    public function down(): void
    {
        // 回滚：从加密列解密还原明文（保持与 up 对称的可逆性）
        DB::table('users')->whereNotNull('api_key_encrypted')->orderBy('user_id')->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                $plain = null;

                try {
                    $plain = Crypt::decryptString((string) $user->api_key_encrypted);
                } catch (Throwable) {
                    $plain = Str::random(60); // 解密失败（APP_KEY 变更等）：重新生成，保证可回滚
                }

                DB::table('users')->where('user_id', $user->user_id)->update([
                    'api_key' => $plain,
                    'api_key_lookup' => null,
                    'api_key_encrypted' => null,
                ]);
            }
        }, 'user_id');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['api_key_lookup']);
            $table->dropColumn(['api_key_lookup', 'api_key_encrypted']);
        });
    }
};
