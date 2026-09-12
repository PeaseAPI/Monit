<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * settings 键修复（十九轮：读键全集审计发现）：
 *
 * 1. email_notices_is_enabled 前缀断裂——后台「分析设置」组保存
 *    "analytics.email_notices_is_enabled"（saveSettings 拼 "{$group}.{$key}"），
 *    但 CoreDataSeeder 历史写入裸键，WebsitesLimitNoticeCommand 读裸键——
 *    后台保存对该 Cron 永不生效。统一为 analytics. 前缀（视图回显读法本就正确）。
 *
 * 2. offload.offload_is_enabled 死开关——后台可保存、视图可显示，但全仓零运行时
 *    消费（Offload 启用判据 = offload.offload_storage_driver 配置 + 插件设置回落），
 *    属「显示有效实际无效」的 UX 陷阱，随规则与视图字段一并移除。
 */
return new class extends Migration
{
    /**
     * 执行迁移：email_notices 裸键搬家 → 死开关残键清理
     */
    public function up(): void
    {
        // 断裂键搬家：裸键 → analytics. 前缀。仅当目标键缺失时搬移（后台可能
        // 已保存过 analytics 组，不覆盖管理员配置），随后清理源键
        $source = DB::table('settings')->where('key', 'email_notices_is_enabled')->value('value');

        if ($source !== null && DB::table('settings')->where('key', 'analytics.email_notices_is_enabled')->doesntExist()) {
            DB::table('settings')->insert([
                'key' => 'analytics.email_notices_is_enabled',
                'value' => $source,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('settings')->where('key', 'email_notices_is_enabled')->delete();

        // 死开关残键清理（零运行时消费）
        DB::table('settings')->where('key', 'offload.offload_is_enabled')->delete();
    }

    /**
     * 回滚：空操作
     *
     * 键格式统一为不可逆的语义修正（原断裂态本身即 bug），
     * 死开关为零消费残值，无数据丢失风险。
     */
    public function down(): void
    {
        //
    }
};
