<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M11 计划任务（规格 §13）：cron_key 鉴权 + 子任务端点
 */
class CronEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_cron_requires_valid_key(): void
    {
        config(['app.cron_key' => 'secret-key-123']);

        $this->getJson('/cron')->assertStatus(403);
        $this->getJson('/cron?key=wrong')->assertStatus(403);
        $this->getJson('/cron?key=secret-key-123')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_cron_subtask_routing(): void
    {
        config(['app.cron_key' => 'secret-key-123']);

        $this->getJson('/cron/email_reports?key=secret-key-123')->assertOk()->assertJsonPath('task', 'email_reports');
        $this->getJson('/cron/broadcasts?key=secret-key-123')->assertOk()->assertJsonPath('task', 'broadcasts');
        $this->getJson('/cron/push_notifications?key=secret-key-123')->assertOk()->assertJsonPath('task', 'push_notifications');

        // 未知子任务 404（路由 whereIn 约束）
        $this->getJson('/cron/unknown?key=secret-key-123')->assertStatus(404);

        // 子任务同样要求 key
        $this->getJson('/cron/broadcasts')->assertStatus(403);
    }
}
