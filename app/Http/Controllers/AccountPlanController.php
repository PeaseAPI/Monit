<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 用户套餐管理控制器
 * 规格书 §6.2.5：/account-plan - 当前套餐与续费
 */
class AccountPlanController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $currentPlan = Plan::find($user->plan_id);
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();

        return view('account.plan', compact('user', 'currentPlan', 'plans'));
    }

    /**
     * 兑换码兑换套餐（规格 §6.2.5 /account-plan）
     */
    public function redeemCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64',
        ]);

        $code = Code::where('code', $validated['code'])->first();

        if (! $code) {
            return back()->withErrors(['code' => __('msg.invalid_code')]);
        }

        if ($issue = $code->redemptionIssue($request->user())) {
            return back()->withErrors(['code' => __($issue)]);
        }

        // 并发窗口内计数被打满时拒绝
        if (! $code->recordRedemption($request->user())) {
            return back()->withErrors(['code' => __('msg.code_fully_redeemed')]);
        }

        $code->applyToUser($request->user());

        return back()->with('success', __('msg.code_redeemed_successfully'));
    }
}
