<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\CheckMaintenance;
use App\Http\Middleware\EnforcePlanLimits;
use App\Http\Middleware\EnsureUserActive;
use App\Models\Website;
use App\Policies\WebsitePolicy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // M23 性能优化：高频像素采集走无中间件路由组（无 Session/Cookie 开销）
        then: function (): void {
            Route::middleware([])->group(__DIR__.'/../routes/track.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 像素采集端点：跨站公开 POST，必须免 CSRF（规格书 §4.1）
        $middleware->validateCsrfTokens(except: [
            'pixel-track/*',
            'webhooks/*',
        ]);

        // 注册自定义中间件别名
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'api.key' => AuthenticateApiKey::class,
            'plan_limit' => EnforcePlanLimits::class,
        ]);

        // 维护模式（规格书 §6.1）：settings main.maintenance_is_enabled 开启时非管理员跳转维护页
        // 封禁用户登出（规格书 §2）：status != 1 的已登录会话在下一个请求立即终止
        $middleware->web(append: [
            CheckMaintenance::class,
            EnsureUserActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->withEvents(discover: [
        // 可以注册事件监听器
    ])
    ->create();

/*
|--------------------------------------------------------------------------
| 策略注册（规格书 §4：权限控制）
|--------------------------------------------------------------------------
*/
Gate::policy(Website::class, WebsitePolicy::class);
