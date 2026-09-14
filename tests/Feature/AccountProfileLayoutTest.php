<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 账户页 #tab-profile 布局回归（用户反馈）：
 * 头像卡位于「登录方式」上方、用户名行在「用户 ID」前、防钓鱼码移至「安全」面板、
 * 头像预览脚本兼容 Safari 26（无 URL.createObjectURL）
 */
class AccountProfileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_layout_order_and_relocations(): void
    {
        $user = User::create([
            'name' => '布局', 'email' => 'layout@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);

        $html = $this->actingAs($user)->get('/account')->assertOk()->getContent();

        // 1) 头像卡（含独立上传表单）出现在「登录方式」卡之前
        $this->assertLessThan(
            strpos($html, __('account.signin_methods')),
            strpos($html, 'id="acc-avatar"'),
            '头像卡应渲染在登录方式卡之前'
        );

        // 2) 用户名行位于用户 ID 行之前（登录方式卡内）
        $this->assertLessThan(
            strpos($html, __('account.user_id_label')),
            strpos($html, '>'.__('account.name_label').'<'),
            '用户名应显示在用户 ID 之前'
        );

        // 3) 防钓鱼码仅出现在「安全」面板（label + input = 2 处），个人资料面板不再含该字段
        $this->assertSame(2, substr_count($html, 'acc-antiphishing'));
        $profileStart = strpos($html, __('account.profile_api_desc'));
        $securityStart = strpos($html, 'data-account-panel="security"');
        $this->assertLessThan($securityStart, $profileStart, 'security 面板应位于 profile 面板之后');
        $profileSegment = substr($html, $profileStart, $securityStart - $profileStart);
        $this->assertStringNotContainsString('acc-antiphishing', $profileSegment);
        $this->assertStringContainsString('acc-antiphishing', substr($html, $securityStart));

        // 4) 头像预览兼容脚本存在（函数定义 + onchange 引用），不再内联使用 URL.createObjectURL
        $this->assertSame(2, substr_count($html, 'monitAvatarPreview'));
        $this->assertStringNotContainsString('onchange="var f=this.files[0]', $html);

        // 5) 头像卡与防钓鱼码卡均 hidden 回传 name/email（account.update 必填校验）
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'type="hidden" name="name"'));
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'type="hidden" name="email"'));
    }
}
