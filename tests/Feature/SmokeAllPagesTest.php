<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 第十六轮：全站页面冒烟（应用内核直调，无网络层）
 *
 * 从路由表程序化遍历全部无参 GET 路由，双角色（管理员/普通用户）逐页请求：
 *  - 断言任何页面不出现 5xx（500 类页面崩溃）
 *  - 200 页面内容不含 Whoops / Fatal error / Undefined variable 等 PHP 错误痕迹
 *  - 普通用户访问 /admin/* 必须被拒（403/302），权限边界回归
 *  - api/v1 全 GET 端点以真实 Bearer key 请求，非 5xx
 * 失败聚合统一报告（一次跑出全部问题页）。
 */
class SmokeAllPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    /** 必然非 200 但属正常业务语义的路径（403 守卫/重装守卫/资源型） */
    private array $allowedNon2xx = ['cron'];

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'plan_id' => 'free', 'name' => 'Free', 'order' => 1, 'is_enabled' => true,
            'prices' => ['USD' => ['monthly' => 0, 'annual' => 0, 'lifetime' => 0]],
        ]);

        $this->admin = User::create([
            'name' => 'Smoke Admin', 'email' => Str::random(8).'@test.dev',
            'password' => Hash::make('password123'), 'type' => 1, 'status' => 1,
            'plan_id' => 'free', 'source' => 'direct',
            'plan_settings' => ['websites_limit' => -1],
            'api_key' => Str::random(60),
        ]);
        $this->user = User::create([
            'name' => 'Smoke User', 'email' => Str::random(8).'@test.dev',
            'password' => Hash::make('password123'), 'type' => 0, 'status' => 1,
            'plan_id' => 'free', 'source' => 'direct',
            'plan_settings' => ['websites_limit' => -1],
            'api_key' => Str::random(60),
        ]);
    }

    /** @return array<string, Route> uri => route（仅无参 GET，剔除 logout） */
    private function webGetRoutes(): array
    {
        $out = [];
        foreach ($this->app['router']->getRoutes() as $route) {
            /** @var Route $route */
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = '/'.$route->uri();
            if (str_contains($route->uri(), '{') || str_contains($uri, '/logout')) {
                continue;
            }
            $out[$uri] = $route;
        }

        return $out;
    }

    private function pageIsHealthy(string $content): bool
    {
        return ! preg_match('/Whoops|Fatal error|ParseError|Undefined (variable|property|index|array key)/i', $content);
    }

    public function test_all_pages_as_admin_have_no_server_errors(): void
    {
        $failures = [];
        $this->actingAs($this->admin);

        foreach ($this->webGetRoutes() as $uri => $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                continue; // api 走 Bearer，另测
            }
            $response = $this->get($uri);
            $status = $response->baseResponse->getStatusCode();

            // /maintenance 设计即 503（规格书 §6.1 维护页语义），豁免
            $isMaintenancePage = $uri === '/maintenance' && $status === 503;

            if ($status >= 500 && ! $isMaintenancePage) {
                $failures[] = "$uri -> $status";
                continue;
            }
            if ($status === 200 && ! $this->pageIsHealthy($response->getContent())) {
                $failures[] = "$uri -> 200 但内容含 PHP 错误痕迹";
            }
        }

        $this->assertSame([], $failures, "管理员访问存在异常页面：\n".implode("\n", $failures));
    }

    public function test_all_pages_as_user_have_no_server_errors_and_admin_is_blocked(): void
    {
        $failures = [];
        $this->actingAs($this->user);

        foreach ($this->webGetRoutes() as $uri => $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $response = $this->get($uri);
            $status = $response->baseResponse->getStatusCode();
            $isMaintenancePage = $uri === '/maintenance' && $status === 503;

            // 权限边界：普通用户访问 admin 面必须被拒（403 拒绝 / 302 重定向均可，唯独不可 200）
            if (str_starts_with($route->uri(), 'admin')) {
                if ($status === 200) {
                    $failures[] = "越权: 普通用户可访问 $uri -> 200";
                }

                continue;
            }

            if ($status >= 500 && ! $isMaintenancePage) {
                $failures[] = "$uri -> $status";
                continue;
            }
            if ($status === 200 && ! $this->pageIsHealthy($response->getContent())) {
                $failures[] = "$uri -> 200 但内容含 PHP 错误痕迹";
            }
        }

        $this->assertSame([], $failures, "普通用户访问存在异常页面：\n".implode("\n", $failures));
    }

    public function test_api_v1_get_endpoints_with_bearer_key_have_no_server_errors(): void
    {
        $failures = [];

        foreach (['user' => $this->user, 'admin' => $this->admin] as $role => $u) {
            $this->actingAs($u);
            foreach ($this->webGetRoutes() as $uri => $route) {
                if (! str_starts_with($route->uri(), 'api/')) {
                    continue;
                }
                $response = $this->get($uri, ['Authorization' => 'Bearer '.$u->api_key]);
                $status = $response->status();
                if ($status >= 500) {
                    $failures[] = "[$role] $uri -> $status";
                    continue;
                }
                if ($status === 200 && ! $this->pageIsHealthy($response->getContent())) {
                    $failures[] = "[$role] $uri -> 200 但内容含 PHP 错误痕迹";
                }
            }
        }

        $this->assertSame([], $failures, "API v1 GET 端点存在异常：\n".implode("\n", $failures));
    }
}
