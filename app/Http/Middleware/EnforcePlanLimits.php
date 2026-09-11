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
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $planLimitService = new PlanLimitService;

        // 数量限额键（*_limit）没有布尔开关语义：缺键=不限，直接进入配额检查。
        // 修复前数量键缺键会被 isFeatureEnabled 的 (bool)(... ?? false) 判为
        // 「未启用」——生产套餐 quota() 未收录 annotations_limit /
        // dashboard_views_limit 等键时，全部付费用户的对应功能被误禁
        if (! str_ends_with($feature, '_limit') && ! $planLimitService->isFeatureEnabled($user, $feature)) {
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
