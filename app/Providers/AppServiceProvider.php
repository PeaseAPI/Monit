<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Website;
use App\Support\PluginManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 网站所有权检查（路由 can:own,website）
        // 支持两种调用方式：
        //   1. Route Model Binding 传入 Website 模型
        //   2. 路由参数直接传入整数 ID（此时自动查询）
        Gate::define('own', function (User $user, mixed $website): bool {
            if (! $website instanceof Website) {
                $website = Website::findOrFail((int) $website);
            }

            return (int) $user->user_id === (int) $website->user_id || $user->isAdmin();
        });

        // 启动已激活插件（规格书 §14：init.php 注册路由 / 指令 / 监听器）
        try {
            PluginManager::boot();
        } catch (\Throwable $e) {
            report($e);
        }

        // Email Shield 插件（规格 §14.9）：@email_shield 指令输出混淆邮箱
        Blade::directive('email_shield', function (string $expression): string {
            return "<?php echo app(\\App\\Services\\EmailShieldService::class)->obfuscate({$expression}); ?>";
        });
        Blade::directive('email_shield_link', function (string $expression): string {
            return "<?php echo app(\\App\\Services\\EmailShieldService::class)->link({$expression}); ?>";
        });
    }
}
