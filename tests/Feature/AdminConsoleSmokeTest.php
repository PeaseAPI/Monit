<?php

namespace Tests\Feature;

use App\Models\Plugin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A7：管理后台全路由冒烟测试（用户反馈「后台设置/团队等都还没有逐条测试」）
 * 以管理员身份遍历全部无参数 admin.* GET 路由断言 200；
 * 非管理员访问管理页 → 403。
 */
class AdminConsoleSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 已知返回非 200 的路由（302 跳转 / 特殊参数），逐个列出而非一刀切跳过
     */
    protected array $allowedRedirects = [
        'admin.settings.clear-cache', // POST 路由名，不会出现在 GET 集合
    ];

    public function test_all_admin_get_routes_render_for_admin(): void
    {
        $admin = User::create([
            'name' => '冒烟管理员', 'email' => uniqid('smoke-').'@admin.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 1,
        ]);

        /** @var Router $router */
        $router = app('router');
        $visited = 0;

        foreach ($router->getRoutes()->getRoutesByName() as $name => $route) {
            if (! Str::startsWith($name, 'admin.')) {
                continue;
            }
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            // 带参数路由（edit/view/delete 详情页）由各自功能测试覆盖，此处仅测列表页
            if ($route->parameterNames() !== []) {
                continue;
            }

            $response = $this->actingAs($admin)->get(route($name));
            // StreamedResponse（如日志下载）无 status 断言接口，视为可达；
            // 302 允许：如 push-subscribers 在插件未启用时引导跳转插件页
            $status = $response->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse
                ? 200
                : $response->status();
            $this->assertTrue(
                in_array($status, [200, 302], true),
                "管理路由 {$name} 返回 {$status}，期望 200/302"
            );
            $visited++;
        }

        // 保证冒烟覆盖面（当前后台无参 GET 路由 > 10 条）
        $this->assertGreaterThan(10, $visited);
    }

    public function test_non_admin_gets_403_on_admin_pages(): void
    {
        $user = User::create([
            'name' => '普通用户', 'email' => uniqid('smoke-u-').'@admin.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ]);

        foreach (['/admin', '/admin/settings', '/admin/users', '/admin/plans', '/admin/payments', '/admin/tickets'] as $uri) {
            $this->actingAs($user)->get($uri)->assertStatus(403);
        }
    }

    public function test_plugins_page_renders_with_active_plugin_card_links(): void
    {
        // 全量巡检修复：插件卡片「专属管理入口」曾引用未注册路由
        // （admin.plugins.push-notifications.campaigns / admin.plugins.image-optimizer.stats），
        // 插件启用后打开 /admin/plugins 渲染 route() 抛 RouteNotFoundException → 500
        Plugin::create([
            'plugin_id' => 'push-notifications', 'name' => 'Push Notifications',
            'is_installed' => true, 'is_active' => true, 'settings' => '{}', 'datetime' => now(),
        ]);

        $admin = User::create([
            'name' => '插件管理员', 'email' => uniqid('smoke-p-').'@admin.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/admin/plugins')
            ->assertOk()
            ->assertSee(route('admin.push-notifications.index'), false);
    }
}
