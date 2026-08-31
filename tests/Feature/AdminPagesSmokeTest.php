<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 后台全页面冒烟：以管理员身份访问所有无参数 admin GET 路由
 * 兜底列表页/设置页渲染错误（字段名/语法/语言键遗漏在 CI 直接暴露）
 */
class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_pages_render_successfully(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'smoke@example.com', 'password' => bcrypt('x'),
            'status' => 1, 'type' => 1, 'plan_id' => 'custom', 'plan_settings' => [],
        ]));

        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->getName() ?? '', 'admin.')) {
                continue;
            }
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            // 含路径参数的路由（详情/编辑页）由各自功能测试覆盖
            if (str_contains($route->uri(), '{')) {
                continue;
            }

            $response = $this->get($route->uri());

            // 流式下载类响应（导出/备份）无 status() 方法，跳过状态断言
            if ($response->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
                $checked++;
                continue;
            }

            $this->assertContains(
                $response->status(),
                [200, 302],
                "GET {$route->uri()}（{$route->getName()}）返回 {$response->status()}，预期 200/302（302=兼容重定向）"
            );
            $checked++;
        }

        $this->assertGreaterThan(20, $checked, '应至少覆盖 20 个后台页面，实际 '.$checked);
    }
}
