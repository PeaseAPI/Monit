<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\CheckMaintenance;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnforcePlanLimits;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\SeoFeatureEnabled;
use App\Http\Middleware\SetLocale;
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
            // 安装向导同样无中间件（未安装时 Session 表/APP_KEY 均未就绪，走 web 组必 500）
            Route::middleware([])->group(__DIR__.'/../routes/install.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 安装守卫（全局最前）：未安装时一切请求 302 到 /install 网页向导（规格 §15.3/§19），
        // 先于 web 组执行——未安装时 Session/Cookie/业务中间件不会因数据库未就绪而 500
        $middleware->prepend(EnsureInstalled::class);

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
            'seo.feature' => SeoFeatureEnabled::class,
        ]);

        // 注：TrustProxies 不在此配置——withMiddleware 闭包在 HTTP kernel 构造期执行
        //（早于 .env/config 加载，env() 只能拿到默认值）；实际配置见
        // AppServiceProvider::boot()（provider boot 阶段 .env 已加载、中间件未执行）

        // 维护模式（规格书 §6.1）：settings main.maintenance_is_enabled 开启时非管理员跳转维护页
        // 封禁用户登出（规格书 §2）：status != 1 的已登录会话在下一个请求立即终止
        // 前台语言切换（/locale/{code} → session → 本中间件 setLocale）
        // 平台响应头（main 组）：force_https 重定向 / iframe 防劫持 / AI 爬虫 / Referrer-Policy
        $middleware->web(append: [
            CheckMaintenance::class,
            EnsureUserActive::class,
            SetLocale::class,
            \App\Http\Middleware\ApplyPlatformHeaders::class,
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
