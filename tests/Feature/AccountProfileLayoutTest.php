<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 账户页 #tab-profile / #tab-security 布局回归（用户反馈 47-48 轮）：
 * 头像卡位于「登录方式」上方、用户名行在「用户 ID」前、「管理你的个人资料和 API 密钥」标题移除、
 * 整页居中、防钓鱼码移至「安全」面板、API 密钥完整显示（break-all）+ 复制按钮、
 * 头像预览脚本兼容 Safari 26（无 URL.createObjectURL）
 */
class AccountProfileLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::create([
            'name' => '布局', 'email' => 'layout@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
    }

    public function test_profile_layout_order_and_relocations(): void
    {
        $user = $this->makeUser();

        $html = $this->actingAs($user)->get('/account')->assertOk()->getContent();

        // 1) 整页居中（用户反馈：原先 max-w-2xl 靠左很别扭）
        $this->assertStringContainsString('mx-auto max-w-2xl', $html);

        // 2) 头像卡（含独立上传表单）出现在「登录方式」卡之前
        $this->assertLessThan(
            strpos($html, __('account.signin_methods')),
            strpos($html, 'id="acc-avatar"'),
            '头像卡应渲染在登录方式卡之前'
        );

        // 3) 用户名行位于用户 ID 行之前（登录方式卡内），且带「修改」按钮 + 行内改名表单
        $this->assertLessThan(
            strpos($html, __('account.user_id_label')),
            strpos($html, '>'.__('account.name_label').'<'),
            '用户名应显示在用户 ID 之前'
        );
        $this->assertStringContainsString('id="rename-form"', $html);
        $this->assertStringContainsString(route('account.rename'), $html);
        // 旧标题已按用户要求移除
        $this->assertStringNotContainsString(__('account.profile_api_desc'), $html);

        // 4) 防钓鱼码仅出现在「安全」面板（label + input = 2 处），个人资料面板不再含该字段
        $this->assertSame(2, substr_count($html, 'acc-antiphishing'));
        $profileStart = strpos($html, __('account.profile_api_desc'));
        $this->assertFalse($profileStart, 'profile_api_desc 文案应已移除');
        $securityStart = strpos($html, 'data-account-panel="security"');
        $profileSegment = substr($html, 0, $securityStart);
        $this->assertStringNotContainsString('acc-antiphishing', $profileSegment);
        $this->assertStringContainsString('acc-antiphishing', substr($html, $securityStart));

        // 5) 头像预览兼容脚本存在（函数定义 + onchange 引用），不再内联使用 URL.createObjectURL
        $this->assertSame(2, substr_count($html, 'monitAvatarPreview'));
        $this->assertStringNotContainsString('onchange="var f=this.files[0]', $html);

        // 6) 头像卡与账单卡均 hidden 回传 name/email（account.update 必填校验）
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'type="hidden" name="name"'));
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'type="hidden" name="email"'));
    }

    public function test_api_key_shown_in_full_with_copy_button(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['api_key' => 'mk_live_0123456789abcdef0123456789abcdef0123'])->save();

        $html = $this->actingAs($user)->get('/account')->assertOk()->getContent();

        // 完整显示：不再 truncate，改用 break-all；渲染完整密钥文本
        $this->assertStringContainsString('id="api-key-code"', $html);
        $this->assertStringContainsString('break-all', $html);
        $this->assertStringNotContainsString('truncate rounded-xl bg-zinc-100', $html);
        $this->assertStringContainsString('mk_live_0123456789abcdef0123456789abcdef0123', $html);
        // 一键复制按钮
        $this->assertStringContainsString('monitCopyApiKey', $html);
    }

    public function test_api_key_copy_button_hidden_when_not_set(): void
    {
        $user = $this->makeUser();

        $html = $this->actingAs($user)->get('/account')->assertOk()->getContent();

        $this->assertStringContainsString('id="api-key-code"', $html);
        // 未设置密钥时不渲染复制按钮（仅函数定义存在，无 onclick 引用）
        $this->assertStringNotContainsString('onclick="monitCopyApiKey(this)"', $html);
    }
}
