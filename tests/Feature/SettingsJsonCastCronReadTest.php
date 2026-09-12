<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Typed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 回归（三十二轮）：cron 命令读取 settings 必须走模型 cast（Setting::query()），
 * 不得 DB::table 直读——AdminSettings::saveSettings 写入的字符串经 json cast 落列后
 * 是带引号 JSON（如 "0" 的列原文为 "\"0\""），直读会拿到 '"0"'：
 * (bool)'"0"' === true 导致 0=关闭 误启用、Typed::int('"7"') === 0 导致天数静默清零、
 * === 'false' 判断失效导致已关闭功能误启用。
 *
 * 两种行形态都必须语义正确：
 * - 后台保存形态（模型写入：字符串/bool → 带引号 JSON）
 * - 迁移/seed 形态（DB 直写裸 JSON：int/bool）
 */
class SettingsJsonCastCronReadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 后台保存形态：saveSettings 路径（模型写入字符串 '0'）→ 0 = 关闭语义成立
     */
    public function test_auto_delete_unconfirmed_users_closed_with_saved_zero_string(): void
    {
        // 模拟 AdminSettings::saveSettings 提交 '0'（表单值经 validate 后仍是字符串）
        Setting::query()->create(['key' => 'users.auto_delete_unconfirmed_users', 'value' => '0']);

        $user = User::create([
            'name' => 'Unconfirmed', 'email' => 'unconfirmed32@example.com',
            'password' => bcrypt('x'), 'status' => 0, 'plan_id' => 'free', 'type' => 0,
            'created_at' => now()->subDays(5), 'email_verified_at' => null,
        ]);

        $this->artisanCmd('monit:auto-delete-unconfirmed-users')
            ->expectsOutputToContain('未启用')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['user_id' => $user->user_id]);
    }

    /**
     * 后台保存形态：字符串 '3'（配置天数）→ 命令读到 3 并生效（修复前 Typed::int('"3"')=0 → 兜底 3 天，恰好巧合；
     * 用 5 天配置区分：修复前读 0 → 兜底 3 天误删 4 天前用户，修复后读 5 → 4 天前用户保留）
     */
    public function test_auto_delete_unconfirmed_users_uses_saved_day_string(): void
    {
        Setting::query()->create(['key' => 'users.auto_delete_unconfirmed_users', 'value' => '5']);

        $safe = User::create([
            'name' => 'Safe4d', 'email' => 'safe4d32@example.com',
            'password' => bcrypt('x'), 'status' => 0, 'plan_id' => 'free', 'type' => 0,
            'email_verified_at' => null,
        ]);
        $safe->forceFill(['created_at' => now()->subDays(4)])->save();
        $old = User::create([
            'name' => 'Old6d', 'email' => 'old6d32@example.com',
            'password' => bcrypt('x'), 'status' => 0, 'plan_id' => 'free', 'type' => 0,
            'email_verified_at' => null,
        ]);
        $old->forceFill(['created_at' => now()->subDays(6)])->save();

        $this->artisanCmd('monit:auto-delete-unconfirmed-users')->assertSuccessful();

        // 5 天配置：4 天前的保留、6 天前的删除（修复前：读到 0 → 兜底 3 天 → 4 天前的也被误删）
        $this->assertDatabaseHas('users', ['user_id' => $safe->user_id]);
        $this->assertDatabaseMissing('users', ['user_id' => $old->user_id]);
    }

    /**
     * 后台保存形态：bool 字段保存为字符串 'false' → 「未启用」判断成立
     * （修复前直读 '"false"'：(bool)true 且 !== 'false' → 误启用 → 会误发配额通知）
     */
    public function test_websites_limit_notice_closed_with_saved_false_string(): void
    {
        Mail::fake();
        Setting::query()->create(['key' => 'analytics.email_notices_is_enabled', 'value' => 'false']);

        $this->artisanCmd('monit:websites-limit-notice')->assertSuccessful();

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    /**
     * 后台保存形态：字符串 'true' → 功能启用（走到配额检查循环而非「未启用」分支）
     */
    public function test_websites_limit_notice_enabled_with_saved_true_string(): void
    {
        Mail::fake();
        Setting::query()->create(['key' => 'analytics.email_notices_is_enabled', 'value' => 'true']);

        $this->artisanCmd('monit:websites-limit-notice')
            ->expectsOutputToContain('已发送 0 封配额超限通知')
            ->assertSuccessful();

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    /**
     * 迁移/seed 形态（DB 直写裸 JSON int 30）→ 兼容：天数 30 正确读取
     */
    public function test_migration_seed_form_still_reads_int(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'users.auto_delete_inactive_users'], ['value' => 30]);

        $value = Setting::query()->where('key', 'users.auto_delete_inactive_users')->value('value');
        $this->assertSame(30, Typed::int($value));
    }
}
