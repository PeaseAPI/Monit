<?php

namespace App\Providers;

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
        Gate::define('own', function (\App\Models\User $user, \App\Models\Website $website): bool {
            return (int) $user->user_id === (int) $website->user_id || $user->isAdmin();
        });
    }
}
