<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * settings 键格式统一：users 组断裂键修复 + seeder 死键清理
 *
 * 背景（继 2026_09_12_000001 配置源统一专项后续审计发现）：
 *
 * 1. 键前缀断裂——后台「用户设置」组保存 "users.*" 点分键，但三个
 *    用户清理 Cron 读的是裸键或 "main.*" 前缀，导致后台保存的配置
 *    对 Cron 永远不生效：
 *    - AutoDeleteInactiveUsersCommand / UsersDeletionReminderCommand
 *      读裸键 auto_delete_inactive_users、user_deletion_reminder
 *    - AutoDeleteUnconfirmedUsersCommand 读 main.auto_delete_unconfirmed_users
 *
 * 2. 键语义断裂——后台把"天数"配置渲染成 boolean 复选框保存
 *    （auto_delete_inactive_users / user_deletion_reminder /
 *    auto_delete_unconfirmed_users），与 Cron 的天数语义不符；
 *    本迁移随「合并为单键天数（0=关闭）」的表单改造清理旧 boolean 值。
 *
 * 3. 死 UI 配置——users 组 5 个开关零消费（运行时真源在别处）：
 *    register_is_enabled（真源 main.registration_is_enabled）、
 *    api_is_enabled（真源 main.api_is_enabled）、
 *    register_display_newsletter_checkbox / account_display_newsletter_checkbox /
 *    login_rememberme_checkbox_is_checked（无任何消费）。
 *
 * 4. seeder 死键——CoreDataSeeder 历史写入的裸键与运行时消费格式
 *    不一致或零消费：default_language / default_timezone（消费方读
 *    main.* 前缀）、user_registration_is_enabled、
 *    admin_user_registration_notification_is_enabled、
 *    email_verification_is_enabled、last_cron_execution、
 *    items_per_page、email_reports_is_enabled（零消费）。
 */
return new class extends Migration
{
    /**
     * 键搬家映射：源键（旧格式） => 目标键（users 组点分键）
     *
     * 仅当目标键缺失或现值非数值、且源值为数值时搬移，
     * 避免用默认值覆盖后台已配置的天数。
     *
     * @var array<string, string>
     */
    private const MOVE_NUMERIC = [
        'auto_delete_inactive_users' => 'users.auto_delete_inactive_users',
        'user_deletion_reminder' => 'users.user_deletion_reminder',
        'main.auto_delete_unconfirmed_users' => 'users.auto_delete_unconfirmed_users',
    ];

    /**
     * 零消费死键（含 5 个死 UI 开关与 seeder 残键）
     *
     * @var list<string>
     */
    private const DEAD_KEYS = [
        'users.register_is_enabled',
        'users.api_is_enabled',
        'users.register_display_newsletter_checkbox',
        'users.account_display_newsletter_checkbox',
        'users.login_rememberme_checkbox_is_checked',
        'users.auto_delete_unconfirmed_users_days',
        'user_registration_is_enabled',
        'admin_user_registration_notification_is_enabled',
        'email_verification_is_enabled',
        'last_cron_execution',
        'items_per_page',
        'email_reports_is_enabled',
    ];

    /**
     * 执行迁移：断裂键搬家 → 清死键 → 清搬家源键
     */
    public function up(): void
    {
        foreach (self::MOVE_NUMERIC as $from => $to) {
            $source = DB::table('settings')->where('key', $from)->value('value');

            if ($source === null || ! is_numeric($source)) {
                continue;
            }

            $target = DB::table('settings')->where('key', $to)->value('value');

            if ($target === null || ! is_numeric($target)) {
                DB::table('settings')->where('key', $to)->delete();
                DB::table('settings')->insert([
                    'key' => $to,
                    'value' => $source,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('settings')->where('key', $from)->delete();
        }

        // main.default_language / main.default_timezone：消费方读 main.* 前缀，
        // seeder 历史上写的是裸键——目标缺失时从裸键补位后清理裸键
        foreach (['default_language', 'default_timezone'] as $legacy) {
            $mainKey = "main.{$legacy}";
            $source = DB::table('settings')->where('key', $legacy)->value('value');

            if ($source !== null && DB::table('settings')->where('key', $mainKey)->doesntExist()) {
                DB::table('settings')->insert([
                    'key' => $mainKey,
                    'value' => $source,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('settings')->where('key', $legacy)->delete();
        }

        DB::table('settings')->whereIn('key', self::DEAD_KEYS)->delete();
    }

    /**
     * 回滚：空操作
     *
     * 键格式统一为不可逆的语义修正（原断裂态本身即 bug），
     * 死键为零消费残值，无数据丢失风险。
     */
    public function down(): void
    {
        //
    }
};
