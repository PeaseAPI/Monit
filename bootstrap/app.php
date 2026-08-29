<?php

use App\Http\Middleware\AdminMiddleware;
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
            'api.key' => \App\Http\Middleware\AuthenticateApiKey::class,
            'plan_limit' => \App\Http\Middleware\EnforcePlanLimits::class,
        ]);

        // 维护模式（规格书 §6.1）：settings main.maintenance_is_enabled 开启时非管理员跳转维护页
        $middleware->web(append: [
            \App\Http\Middleware\CheckMaintenance::class,
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
