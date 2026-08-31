<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 任务 C 设置组补齐验证
 * - business 组：原库 settings.business 16 字段（后台「发票信息」选项卡 → settings 表）
 * - cache/health/support：只读运维面板（原系统独立功能页），不接受表单保存
 * - 缓存面板：clear-cache action 清 monit.settings 缓存
 * - 发票接线：AdminInvoice 抬头/票号前缀读取 business 组
 */
class SettingsGroupsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => '任务C用户', 'email' => 'taskc@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ], $attrs));
    }

    /* ---------------- 视图渲染 ---------------- */

    public function test_settings_page_renders_new_tabs(): void
    {
        $admin = $this->makeUser(['email' => 'admin@taskc.dev', 'type' => 1]);

        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('id="panel-business"', false)
            ->assertSee('id="panel-cache"', false)
            ->assertSee('id="panel-health"', false)
            ->assertSee('id="panel-support"', false)
            ->assertSee('品牌名称（发票抬头）')
            ->assertSee('清空缓存')
            ->assertSee('产品版本')
            ->assertSee('发票信息')
            ->assertSee('id="panel-email_shield"', false)
            ->assertSee('启用邮箱防护');
    }

    /* ---------------- business 组保存 ---------------- */

    public function test_business_group_saves_and_reads_back(): void
    {
        $admin = $this->makeUser(['email' => 'admin@taskc.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'business',
            'brand_name' => '蒙尼特科技',
            'invoice_nr_prefix' => 'MON-',
            'name' => '蒙尼特（北京）信息技术有限公司',
            'address' => '中关村大街 1 号',
            'city' => '北京',
            'county' => '海淀区',
            'zip' => '100080',
            'country' => 'CN',
            'email' => 'billing@monit.cn',
            'phone' => '+86 10 12345678',
            'tax_type' => 'VAT',
            'tax_id' => '91110000MA01XXXXXX',
            'custom_key_one' => '开户行',
            'custom_value_one' => '招商银行北京分行',
            'custom_key_two' => '银行账号',
            'custom_value_two' => '6225 0000 0000 0000',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'business.brand_name']);
        $this->assertDatabaseHas('settings', ['key' => 'business.tax_id']);

        $this->assertSame('蒙尼特科技', Settings::get('business.brand_name'));
        $this->assertSame('MON-', Settings::get('business.invoice_nr_prefix'));
        $this->assertSame('VAT', Settings::get('business.tax_type'));

        // 保存后设置页回显
        $this->actingAs($admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('蒙尼特科技')
            ->assertSee('91110000MA01XXXXXX');
    }

    public function test_business_validation_rejects_bad_email_and_tax_type(): void
    {
        $admin = $this->makeUser(['email' => 'admin@taskc.dev', 'type' => 1]);

        $this->actingAs($admin)->put('/admin/settings', [
            'group' => 'business',
            'email' => 'not-an-email',
            'tax_type' => 'QST',
        ])->assertSessionHasErrors(['email', 'tax_type']);

        $this->assertDatabaseMissing('settings', ['key' => 'business.email']);
    }

    public function test_readonly_groups_cannot_be_submitted(): void
    {
        $admin = $this->makeUser(['email' => 'admin@taskc.dev', 'type' => 1]);

        foreach (['cache', 'health', 'support'] as $group) {
            $this->actingAs($admin)->put('/admin/settings', [
                'group' => $group,
                'driver' => 'redis',
            ])->assertSessionHasErrors('error');

            $this->assertDatabaseMissing('settings', ['key' => $group.'.driver']);
        }
    }

    /* ---------------- 缓存面板 ---------------- */

    public function test_clear_cache_action_flushes_settings_cache(): void
    {
        $admin = $this->makeUser(['email' => 'admin@taskc.dev', 'type' => 1]);

        Settings::set('main.title', '缓存测试站点');
        // set 后需一次读取触发 Cache::remember 重建，此后条目存在
        $this->assertSame('缓存测试站点', Settings::get('main.title'));
        $this->assertTrue(Cache::has('monit.settings'));

        $this->actingAs($admin)->post('/admin/settings/clear-cache')
            ->assertRedirect();

        $this->assertFalse(Cache::has('monit.settings'));
        $this->assertSame('缓存测试站点', Settings::get('main.title'));
    }

    public function test_non_admin_cannot_clear_cache(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/admin/settings/clear-cache')->assertStatus(403);
    }

    /* ---------------- 发票接线 ---------------- */

    public function test_invoice_uses_business_settings(): void
    {
        $buyer = $this->makeUser(['email' => 'buyer@taskc.dev', 'type' => 0]);
        Settings::set('business.brand_name', '蒙尼特科技');
        Settings::set('business.invoice_nr_prefix', 'MON-');
        Settings::set('business.tax_type', 'VAT');
        Settings::set('business.tax_id', '91110000MA01XXXXXX');

        $payment = Payment::create([
            'user_id' => $buyer->user_id,
            'name' => $buyer->name,
            'email' => $buyer->email,
            'plan_id' => 'pro',
            'payment_processor' => 'stripe',
            'type' => 'one_time',
            'frequency' => 'monthly',
            'status' => 1,
            'total_amount' => 9.99,
            'currency' => 'USD',
            'datetime' => now(),
        ]);

        $this->actingAs($this->makeUser(['email' => 'admin@taskc.dev', 'type' => 1]))
            ->get("/admin/payments/{$payment->payment_id}/invoice")
            ->assertOk()
            ->assertSee('蒙尼特科技')
            ->assertSee('MON-'.str_pad((string) $payment->payment_id, 6, '0', STR_PAD_LEFT))
            ->assertSee('VAT: 91110000MA01XXXXXX');

        // 信用票据票号保持 CN- 前缀
        $this->actingAs($this->makeUser(['email' => 'admin2@taskc.dev', 'type' => 1]))
            ->get("/admin/payments/{$payment->payment_id}/credit-note")
            ->assertOk()
            ->assertSee('CN-'.str_pad((string) $payment->payment_id, 6, '0', STR_PAD_LEFT));
    }
}
