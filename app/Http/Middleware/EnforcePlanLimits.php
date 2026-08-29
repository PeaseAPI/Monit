<?php

namespace App\Http\Middleware;

use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 套餐配额限制中间件
 * 规格书 §10.2：在创建资源前检查用户套餐配额
 *
 * 用法：Route::middleware('plan_limit:websites_limit')->...
 * 参数对应 PlanLimitService::checkLimit() 的 $feature 键
 */
class EnforcePlanLimits
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $planLimitService = new PlanLimitService();

        // 检查功能是否启用
        if (! $planLimitService->isFeatureEnabled($user, $feature)) {
            return back()->withErrors([
                'plan' => __('plan.feature_not_enabled'),
            ])->withInput();
        }

        // 检查配额
        if (! $planLimitService->checkLimit($user, $feature)) {
            return back()->withErrors([
                'plan' => __('plan.limit_reached', ['feature' => $feature]),
            ])->withInput();
        }

        return $next($request);
    }
}
