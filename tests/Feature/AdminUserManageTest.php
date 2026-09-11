<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Support\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 管理后台用户管理对标测试（monit.cn /admin/user-update + user-view）
 *
 * 覆盖：编辑页全量字段渲染、plan_settings 保存、用户级覆盖语义、
 * 全息档案展示、toggleStatus 三态、创建本土默认、CNY 元后缀格式化
 */
class AdminUserManageTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin-um@example.test', 'password' => bcrypt('secret123'),
            'status' => 1, 'type' => 1, 'plan_id' => 'custom', 'plan_settings' => [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function targetUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Target', 'email' => 'target-um@example.test', 'password' => bcrypt('secret123'),
            'status' => 1, 'type' => 0, 'plan_id' => 'free', 'plan_settings' => null,
        ], $overrides));
    }

    public function test_edit_page_renders_all_monit_fields(): void
    {
        $this->actingAs($this->adminUser());
        $user = $this->targetUser(['plan_settings' => ['websites_limit' => 5, 'export' => ['csv']]]);

        $response = $this->get(route('admin.users.edit', $user->user_id));

        $response->assertOk();
        foreach ([
            'name="status"', 'name="type"', 'name="referred_by"', 'name="plan_id"',
            'name="plan_trial_done"', 'name="plan_expiration_date"', 'name="password_confirmation"',
            'plan_settings[websites_limit]', 'plan_settings[sessions_events_limit]',
            'plan_settings[sessions_replays_time_limit]', 'plan_settings[dashboard_views_limit]',
            'plan_settings[affiliate_commission_percentage]', 'plan_settings[no_ads]',
            'plan_settings[white_labeling_is_enabled]', 'plan_settings[export][]',
        ] as $needle) {
            $this->assertStringContainsString($needle, (string) $response->getContent(), "编辑页缺少字段 {$needle}");
        }
    }

    public function test_update_saves_plan_settings_privileges_and_status(): void
    {
        $this->actingAs($this->adminUser());
        $user = $this->targetUser();

        $response = $this->put(route('admin.users.update', $user->user_id), [
            'name' => 'Target X', 'email' => $user->email,
            'status' => 2, 'type' => 1,
            'plan_id' => 'custom', 'plan_trial_done' => '1',
            'plan_settings' => [
                'websites_limit' => 7,
                'sessions_events_limit' => -1,
                'sessions_events_retention' => 90,
                'events_children_limit' => -1,
                'events_children_retention' => 30,
                'sessions_replays_limit' => 100,
                'sessions_replays_retention' => 30,
                'sessions_replays_time_limit' => 5,
                'websites_heatmaps_limit' => -1,
                'websites_goals_limit' => -1,
                'annotations_limit' => 10,
                'domains_limit' => 2,
                'dashboard_views_limit' => -1,
                'affiliate_commission_percentage' => 35,
                'email_reports_is_enabled' => '1',
                'api_is_enabled' => '1',
                'export' => ['csv', 'pdf'],
            ],
            'password' => 'new-password-1', 'password_confirmation' => 'new-password-1',
        ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertSame(2, $user->status);
        $this->assertSame(1, $user->type);
        $this->assertTrue($user->plan_trial_done);
        $this->assertSame(7, $user->plan_settings['websites_limit']);
        $this->assertSame(35, $user->plan_settings['affiliate_commission_percentage']);
        $this->assertTrue((bool) $user->plan_settings['email_reports_is_enabled']);
        $this->assertFalse((bool) $user->plan_settings['no_ads']);
        $this->assertSame(['csv', 'pdf'], $user->plan_settings['export']);
        $this->assertTrue(Hash::check('new-password-1', (string) $user->password));
    }

    public function test_update_rejects_mismatched_password_confirmation(): void
    {
        $this->actingAs($this->adminUser());
        $user = $this->targetUser();

        $response = $this->put(route('admin.users.update', $user->user_id), [
            'name' => 'T', 'email' => $user->email, 'status' => 1, 'type' => 0,
            'password' => 'new-password-1', 'password_confirmation' => 'different-99',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_get_plan_settings_user_level_overrides_plan_defaults(): void
    {
        Plan::create([
            'plan_id' => 'pro', 'name' => 'Pro', 'is_enabled' => true, 'order' => 1,
            'settings' => ['websites_limit' => 20, 'sessions_events_limit' => -1, 'no_ads' => true],
        ]);

        // 无用户级覆盖：套餐默认
        $user = $this->targetUser(['plan_id' => 'pro']);
        $this->assertSame(20, $user->getPlanSettings()['websites_limit']);

        // 用户级单键覆盖：其余键沿用套餐
        $user->forceFill(['plan_settings' => ['websites_limit' => 3]])->save();
        $merged = $user->getPlanSettings();
        $this->assertSame(3, $merged['websites_limit']);
        $this->assertSame(-1, $merged['sessions_events_limit']);
        $this->assertTrue((bool) $merged['no_ads']);
    }

    public function test_view_page_shows_full_profile(): void
    {
        $this->actingAs($this->adminUser());
        $user = $this->targetUser([
            'api_key' => 'pk_test_api_key_xyz',
            'anti_phishing_code' => 'SAFE123', 'twofa_is_enabled' => 1,
            'country' => 'CN', 'city_name' => 'Shenzhen', 'os_name' => 'macOS',
            'billing' => ['name' => '测试公司', 'city' => '唐山市', 'tax_id' => '91130000X'],
        ]);

        $response = $this->get(route('admin.users.view', $user->user_id));

        $response->assertOk();
        $content = (string) $response->getContent();
        $this->assertStringContainsString('SAFE123', $content);
        $this->assertStringContainsString('pk_test_api_key_xyz', $content);
        $this->assertStringContainsString('测试公司', $content);
        $this->assertStringContainsString('91130000X', $content);
        $this->assertStringContainsString('macOS', $content);
    }

    public function test_toggle_status_switches_between_active_and_disabled(): void
    {
        $this->actingAs($this->adminUser());
        $user = $this->targetUser(['status' => 1]);

        $this->put(route('admin.users.toggle_status', $user->user_id));
        $this->assertSame(2, $this->freshModel($user)->status);

        $this->put(route('admin.users.toggle_status', $user->user_id));
        $this->assertSame(1, $this->freshModel($user)->status);
    }

    public function test_create_user_defaults_to_cn_locale(): void
    {
        $this->actingAs($this->adminUser());

        $response = $this->post(route('admin.users.store'), [
            'name' => '新人', 'email' => 'newbie-um@example.test', 'password' => 'secret123',
        ]);

        $response->assertRedirect();
        $user = User::where('email', 'newbie-um@example.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('zh_CN', $user->language);
        $this->assertSame('Asia/Shanghai', $user->timezone);
        $this->assertSame(1, $user->status);
        $this->assertSame('admin', $user->source);
    }

    public function test_users_index_filters_by_type_and_disabled_status(): void
    {
        $this->actingAs($this->adminUser());
        $this->targetUser(['status' => 2, 'type' => 0, 'email' => 'banned-um@example.test']);

        $this->get(route('admin.users.index', ['status' => 2]))
            ->assertOk()->assertSee('banned-um@example.test');

        $this->get(route('admin.users.index', ['type' => 1]))
            ->assertOk()->assertDontSee('banned-um@example.test');
    }

    public function test_currency_cny_formats_with_yuan_suffix(): void
    {
        $this->assertSame('9.00 元', Currency::format(9, 'CNY'));
        $this->assertSame('$0.14', Currency::format(0.14, 'USD'));
    }

    public function test_update_preserves_keys_outside_form_and_accepts_seo_keys(): void
    {
        // 修复前整列替换：表单未包含的键（管理员手工配置的 M26 SEO 配额）
        // 在每次保存用户资料时被静默抹掉；且 SEO 键不在白名单，无法经表单配置
        $this->actingAs($this->adminUser());
        $user = $this->targetUser([
            'plan_settings' => ['seo_keywords_limit' => 33, 'websites_limit' => 5],
        ]);

        // 表单只提交部分键（旧编辑页形态），并携带新白名单内的 SEO 键
        $this->put(route('admin.users.update', $user->user_id), [
            'name' => 'Target X', 'email' => $user->email, 'status' => 1, 'type' => 0,
            'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => 8, 'seo_audits_limit' => 500],
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame(8, $user->plan_settings['websites_limit']);
        $this->assertSame(500, $user->plan_settings['seo_audits_limit']); // 新白名单键可配
        $this->assertSame(33, $user->plan_settings['seo_keywords_limit']); // 非表单键保留

        // 显式提交空串 = 清除该键（回归套餐默认），其余键继续保留
        $this->put(route('admin.users.update', $user->user_id), [
            'name' => 'Target X', 'email' => $user->email, 'status' => 1, 'type' => 0,
            'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => ''],
        ])->assertRedirect();

        $user->refresh();
        $this->assertArrayNotHasKey('websites_limit', $user->plan_settings);
        $this->assertSame(33, $user->plan_settings['seo_keywords_limit']);
        $this->assertSame(500, $user->plan_settings['seo_audits_limit']);
    }
}
