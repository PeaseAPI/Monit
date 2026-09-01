<?php

namespace App\Http\Controllers;

use App\Models\DashboardView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 仪表盘视图管理（规格书 §6.2.1：自定义视图 DashboardViews）
 */
class DashboardViewController extends Controller
{
    public function index(Request $request)
    {
        $views = DashboardView::where('user_id', auth()->id())->orderBy('order')->get();

        return view('dashboard-views.index', compact('views'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'settings' => ['required'],
            'order' => ['nullable', 'integer'],
            'website_id' => ['nullable', 'integer', 'exists:websites,website_id'],
        ]);

        DashboardView::create([
            'website_id' => $validated['website_id'] ?? null,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'settings' => $this->normalizeSettings($validated['settings']),
            'order' => $validated['order'] ?? 0,
            'datetime' => now(),
        ]);

        return back()->with('success', __('msg.dashboard_view_created'));
    }

    public function update(Request $request, int $viewId): RedirectResponse
    {
        $view = DashboardView::where('user_id', auth()->id())->findOrFail($viewId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:128'],
            'settings' => ['sometimes'],
            'order' => ['nullable', 'integer'],
        ]);

        $attributes = collect($validated)->except(['settings', 'order'])->all();
        if (array_key_exists('settings', $validated)) {
            $attributes['settings'] = $this->normalizeSettings($validated['settings']);
        }
        if ($request->filled('order')) {
            $attributes['order'] = (int) $request->input('order');
        }

        $view->update($attributes);

        return back()->with('success', __('msg.dashboard_view_updated'));
    }

    /**
     * 表单以 JSON 文本域提交，模型 cast 需要 array —— 统一归一化
     */
    private function normalizeSettings(mixed $settings): array
    {
        if (is_string($settings)) {
            $decoded = json_decode($settings, true);

            return is_array($decoded) ? $decoded : ['raw' => $settings];
        }

        return is_array($settings) ? $settings : [];
    }

    public function destroy(int $viewId): RedirectResponse
    {
        $view = DashboardView::where('user_id', auth()->id())->findOrFail($viewId);
        $view->delete();

        return back()->with('success', __('msg.dashboard_view_deleted'));
    }
}
