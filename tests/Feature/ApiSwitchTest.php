<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M25 审计修复：main.api_is_enabled 设置此前「只存不用」，现于 API 中间件强制
 * - 未设置（null）：API 默认可用（向后兼容）
 * - 显式 'false'：所有 api/v1 鉴权端点 403
 */
class ApiSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Settings::flush();
        parent::tearDown();
    }

    public function test_api_available_by_default(): void
    {
        // 未设置开关：请求到达认证层（401 缺 token，而非 403 disabled）
        $this->getJson('/api/v1/user')->assertStatus(401);
    }

    public function test_api_disabled_returns_403(): void
    {
        Settings::set('main.api_is_enabled', 'false');
        Settings::flush();

        $this->getJson('/api/v1/user')->assertStatus(403)->assertJson(['error' => 'API is disabled']);
    }
}
