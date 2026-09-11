<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Typed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 第十三轮：api_key 存储加密化（查找哈希 + 可逆加密）回归
 * ——明文不再落库；存量 key 无缝迁移；UX（回显/轮换）不变
 */
class ApiKeyEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_key_is_encrypted_at_rest(): void
    {
        $key = Str::random(60);
        $user = User::create([
            'name' => 'Enc Owner',
            'email' => 'enc@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'free',
            'api_key' => $key,
        ]);

        $raw = (array) DB::table('users')->where('user_id', $user->user_id)->first();

        $this->assertNull($raw['api_key'], '明文列必须恒为 NULL');
        $this->assertSame(hash('sha256', $key), $raw['api_key_lookup']);
        $this->assertNotNull($raw['api_key_encrypted']);
        $this->assertStringNotContainsString($key, Typed::string($raw['api_key_encrypted']), '密文中不得出现明文子串');
        $this->assertSame($key, $user->api_key, '模型读取必须解密还原（UX 不变）');
    }

    /** 认证链路：Bearer → lookup 命中 → 解密恒时确认 */
    public function test_api_authentication_works_with_encrypted_key(): void
    {
        $key = Str::random(60);
        $user = User::create($this->userPayload($key));

        $this->withHeader('Authorization', 'Bearer '.$key)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('user.user_id', $user->user_id);
    }

    /** 账号 API 回显完整明文 key（UX 不变——加密化不砍可见性） */
    public function test_account_endpoint_echoes_plaintext_key(): void
    {
        $key = Str::random(60);
        $user = User::create($this->userPayload($key));

        $this->withHeader('Authorization', 'Bearer '.$key)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('user.api_key', $key);
    }

    /** 轮换：新 key 生效、旧 key 失效、明文列仍无残留 */
    public function test_rotation_invalidates_old_key(): void
    {
        $old = Str::random(60);
        $user = User::create($this->userPayload($old));

        $new = $user->generateApiToken();
        $this->assertNotSame($old, $new);

        $raw = (array) DB::table('users')->where('user_id', $user->user_id)->first();
        $this->assertNull($raw['api_key']);
        $this->assertStringNotContainsString($old, Typed::string($raw['api_key_encrypted']));

        $this->withHeader('Authorization', 'Bearer '.$new)->getJson('/api/v1/user')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$old)->getJson('/api/v1/user')->assertUnauthorized();
    }

    /** 清空（api_key=null）：lookup/加密列同步清空，访问立即吊销 */
    public function test_clearing_key_revokes_access(): void
    {
        $key = Str::random(60);
        $user = User::create($this->userPayload($key));

        $user->update(['api_key' => null]);

        $raw = (array) DB::table('users')->where('user_id', $user->user_id)->first();
        $this->assertNull($raw['api_key_lookup']);
        $this->assertNull($raw['api_key_encrypted']);

        $this->withHeader('Authorization', 'Bearer '.$key)->getJson('/api/v1/user')->assertUnauthorized();
    }

    /** 解密失败（APP_KEY 丢失等）fail-closed：读到 null 而非异常 */
    public function test_decrypt_failure_fails_closed(): void
    {
        $key = Str::random(60);
        $user = User::create($this->userPayload($key));

        // 篡改密文（模拟 APP_KEY 变更后不可解）
        DB::table('users')->where('user_id', $user->user_id)->update(['api_key_encrypted' => 'garbage']);

        $this->assertNull($this->freshModel($user)->api_key);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(string $key): array
    {
        return [
            'name' => 'Api Owner',
            'email' => Str::random(8).'@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => -1],
            'api_key' => $key,
        ];
    }
}
