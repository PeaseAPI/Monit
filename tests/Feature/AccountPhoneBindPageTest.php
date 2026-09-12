<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M30：账户页手机号绑定弹窗
 * - /account 页面渲染必含弹窗 partial（未绑定→绑定按钮；已绑定→更改绑定手机号）
 * - 弹窗 JS 嵌值用 @json（此前误用不存在的 js() helper，渲染即 500——本用例防回归）
 */
class AccountPhoneBindPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 开启短信（log provider）+ phone_bind 场景 → 账户页渲染弹窗分支
        Settings::set('sms.sms_is_enabled', true);
        Settings::set('sms.sms_provider', 'log');
        Settings::set('sms.sms_phone_bind_is_enabled', true);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function makeUser(array $extra = []): User
    {
        /** @var array<string, mixed> $attributes */
        $attributes = array_merge([
            'name' => '手机号绑定测试用户', 'email' => uniqid('phone-').'@phone.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ], $extra);

        return User::create($attributes);
    }

    public function test_account_page_renders_phone_bind_modal_for_unbound_user(): void
    {
        $this->actingAs($this->makeUser())->get('/account')
            ->assertOk()
            ->assertSee('phone-bind-modal', false)
            ->assertSee(__('account.phone_bind_btn'), false);
    }

    public function test_account_page_renders_change_phone_button_for_bound_user(): void
    {
        $this->actingAs($this->makeUser(['phone' => '13800138000']))->get('/account')
            ->assertOk()
            ->assertSee('phone-bind-modal', false)
            ->assertSee(__('account.phone_change_btn'), false)
            ->assertSee('13800138000', false);
    }
}
