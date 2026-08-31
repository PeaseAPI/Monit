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
            'price' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['nullable', 'integer'],
            'order' => ['nullable', 'integer'],
            'is_enabled' => ['boolean'],
        ]);

        Plan::create([
            'plan_id' => $validated['plan_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            // 价格结构（规格书 §10.1：月/年/终身）
            'prices' => [
                'monthly' => (float) ($validated['price'] ?? 0),
                'yearly' => (float) ($validated['price'] ?? 0) * 12,
                'lifetime' => (float) ($validated['price'] ?? 0) * 10,
            ],
            'settings' => $this->resolveFeatureSettings($request),
            'trial_days' => (int) ($validated['trial_days'] ?? 0),
            'order' => (int) ($validated['order'] ?? 0),
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
            'price' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['nullable', 'integer'],
            'order' => ['nullable', 'integer'],
            'is_enabled' => ['boolean'],
        ]);

        $plan->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'prices' => [
                'monthly' => (float) ($validated['price'] ?? 0),
                'yearly' => (float) ($validated['price'] ?? 0) * 12,
                'lifetime' => (float) ($validated['price'] ?? 0) * 10,
            ],
            // 合并保留未知键（防止历史自定义配置丢失，规格书 §10.2）
            'settings' => array_merge($plan->settings ?? [], $this->resolveFeatureSettings($request)),
            'trial_days' => (int) ($validated['trial_days'] ?? 0),
            'order' => (int) ($validated['order'] ?? 0),
            // 编辑页有显式勾选框：未勾选即停用
            'is_enabled' => $request->has('is_enabled'),
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', __('msg.plan_updated', ['name' => $plan->name]));
    }

    /**
     * 解析表单功能矩阵输入（features[key]）为 settings 数组
     * bool 型未勾选 -> false；int 型空值 -> 0（规格书 §10.2）
     */
    protected function resolveFeatureSettings(Request $request): array
    {
        $features = (array) config('monit.plan_features', []);
        $input = (array) $request->input('features', []);
        $settings = [];

        foreach ($features as $key => $meta) {
            $raw = $input[$key] ?? null;

            if (($meta['type'] ?? 'bool') === 'int') {
                // 空串 / null 均回退到默认值
                $settings[$key] = ($raw === null || $raw === '')
                    ? (int) ($meta['default'] ?? 0)
                    : (int) $raw;
            } else {
                $settings[$key] = $raw !== null;
            }
        }

        return $settings;
    }

    public function toggleStatus(string $planId): RedirectResponse
    {
        $plan = Plan::findOrFail($planId);
        $plan->update(['is_enabled' => ! $plan->is_enabled]);

        return back()->with('success', __('msg.plan_status_toggled', ['name' => $plan->name, 'status' => $plan->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled')]));
    }
}
