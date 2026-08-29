<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 套餐管理
 * 规格书 §6.3.3：AdminPlans / AdminPlanCreate / AdminPlanUpdate
 */
class AdminPlans extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('order')->get();

                return view('admin.plans.index', compact('plans'))->with('adminNav', 'plans');
    }

    public function create()
    {
                return view('admin.plans.create')->with('adminNav', 'plans');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'string', 'max:64', 'unique:plans,plan_id'],
            'name' => ['required', 'string', 'max:256'],
            'description' => ['nullable', 'string'],
            'prices' => ['nullable', 'json'],
            'settings' => ['nullable', 'json'],
            'trial_days' => ['nullable', 'integer'],
            'order' => ['nullable', 'integer'],
            'is_enabled' => ['boolean'],
        ]);

        Plan::create([
            ...$validated,
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        return redirect()->route('admin.plans.index')
                        ->with('success', __('msg.plan_created', ['name' => $validated['name']]));
    }

        public function edit(string $planId)
    {
        $plan = Plan::findOrFail($planId);

                return view('admin.plans.edit', compact('plan'))->with('adminNav', 'plans');
    }

    public function update(Request $request, string $planId): RedirectResponse
    {
        $plan = Plan::findOrFail($planId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'description' => ['nullable', 'string'],
            'prices' => ['nullable', 'json'],
            'settings' => ['nullable', 'json'],
            'trial_days' => ['nullable', 'integer'],
            'order' => ['nullable', 'integer'],
            'is_enabled' => ['boolean'],
        ]);

        $plan->update($validated);

        return redirect()->route('admin.plans.index')
                        ->with('success', __('msg.plan_updated', ['name' => $plan->name]));
    }

    public function toggleStatus(string $planId): RedirectResponse
    {
        $plan = Plan::findOrFail($planId);
        $plan->update(['is_enabled' => ! $plan->is_enabled]);

                return back()->with('success', __('msg.plan_status_toggled', ['name' => $plan->name, 'status' => $plan->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled')]));
    }
}
