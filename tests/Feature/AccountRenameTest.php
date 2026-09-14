<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 修改用户名（用户反馈：用户名后加「修改」，须验证登录密码）：
 * 正常改名 / 密码错误拒绝 / 缺密码拒绝 / 错误时表单自动展开
 */
class AccountRenameTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::create([
            'name' => '旧名字', 'email' => 'rename@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
    }

    public function test_rename_with_valid_password_updates_name(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/account/rename', [
            'name' => '新名字',
            'current_password' => 'secret123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'name' => '新名字']);
    }

    public function test_rename_rejects_wrong_password(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->from('/account')->post('/account/rename', [
            'name' => '新名字',
            'current_password' => 'wrong-password',
        ]);

        $response->assertRedirect('/account');
        $response->assertSessionHasErrors('current_password');
        $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'name' => '旧名字']);
    }

    public function test_rename_requires_password_and_name(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/account/rename', [])->assertInvalid(['name', 'current_password']);
        $this->actingAs($user)->post('/account/rename', [
            'name' => '新名字',
        ])->assertInvalid('current_password');

        $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'name' => '旧名字']);
    }

    public function test_rename_form_expanded_with_error_after_failed_attempt(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/account')->post('/account/rename', [
            'name' => '新名字', 'current_password' => 'wrong',
        ]);

        $html = $this->actingAs($user)->get('/account')->getContent();

        $this->assertStringContainsString('id="rename-form"', $html);
        // 错误状态下不应再带 hidden（自动展开显示错误信息）
        $this->assertStringNotContainsString('id="rename-form" class="hidden', $html);
    }
}
