<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * N+1 查询回归防护
 *
 * 对核心列表端点断言查询数上限：控制器或视图将来引入
 * 「循环内 lazy relation 访问」时，查询数随行数线性增长即超阈值失败。
 * 阈值刻意宽松（容纳框架基础查询与合理新增），只拦截每行 1 次的 N+1。
 */
class QueryCountRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(array $overrides = []): User
    {
        static $seq = 0;
        $seq++;

        return User::create(array_merge([
            'name' => "User{$seq}", 'email' => "n1-{$seq}@example.test",
            'password' => bcrypt('secret123'), 'status' => 1, 'type' => 0,
            'plan_id' => 'free', 'plan_settings' => null,
        ], $overrides));
    }

    protected function makeWebsite(User $user, string $host): Website
    {
        return Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_'.str_replace('.', '_', $host),
            'name' => 'Site '.$host, 'scheme' => 'https', 'host' => $host,
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);
    }

    protected function assertQueryBudget(callable $request, int $budget, string $label): void
    {
        DB::enableQueryLog();
        $request();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            $budget,
            $count,
            "{$label} 查询数 {$count} 超预算 {$budget}：随行数线性增长，疑似 N+1"
        );
    }

    public function test_admin_users_index_does_not_query_per_row(): void
    {
        $this->actingAs($this->makeUser(['type' => 1, 'email' => 'admin-n1@example.test']));
        for ($i = 0; $i < 50; $i++) {
            $this->makeUser();
        }

        $this->assertQueryBudget(
            fn () => $this->get(route('admin.users.index'))->assertOk(),
            10,
            'admin.users.index（50 行/页，基线 3：列表+count+plans）'
        );
    }

    public function test_admin_index_does_not_query_per_recent_user(): void
    {
        $this->actingAs($this->makeUser(['type' => 1, 'email' => 'admin-n1@example.test']));
        for ($i = 0; $i < 12; $i++) {
            $this->makeUser();
        }

        $this->assertQueryBudget(
            fn () => $this->get(route('admin.index'))->assertOk(),
            24,
            'admin.index（基线 18：8 卡×2 次 count/sum + 2 列表）'
        );
    }

    public function test_dashboard_does_not_query_per_website(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        for ($i = 1; $i <= 12; $i++) {
            $this->makeWebsite($user, "site{$i}.test");
        }

        $this->assertQueryBudget(
            fn () => $this->get(route('dashboard'))->assertOk(),
            26,
            'dashboard（基线 19：StatisticsService 固定聚合，与网站数无关）'
        );
    }
}
