<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 配置源统一专项：清理 admin/settings 五个死配置组的 settings 表残键
 *
 * 背景：pwa / email_shield / image_optimizer / dynamic_og_images /
 * push_notifications 五个设置组为历史遗留死配置——运行时全部从
 * plugins 表（PluginManager::setting）读取，settings 表键零消费。
 * 管理后台对应 tab 已在本次「配置源统一」提交中移除，本迁移清理
 * 表内残键（线上实测仅 image_optimizer.is_enabled 与
 * push_notifications.is_enabled 两条 true 残值，其余组为 0 条）。
 */
return new class extends Migration
{
    /**
     * 死配置组键前缀（settings 表键格式："group.field"）
     *
     * @var list<string>
     */
    private const DEAD_PREFIXES = [
        'pwa.',
        'email_shield.',
        'image_optimizer.',
        'dynamic_og_images.',
        'push_notifications.',
    ];

    /**
     * 执行迁移：逐前缀删除死配置键
     */
    public function up(): void
    {
        foreach (self::DEAD_PREFIXES as $prefix) {
            DB::table('settings')->where('key', 'like', "{$prefix}%")->delete();
        }
    }

    /**
     * 回滚：空操作
     *
     * 死键运行时零消费、无恢复语义；且原值已在移除前人工核实
     * 仅为默认值级别残值（true/空），无数据丢失风险。
     */
    public function down(): void
    {
        //
    }
};
