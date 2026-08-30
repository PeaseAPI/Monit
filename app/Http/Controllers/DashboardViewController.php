<?php

namespace App\Http\Controllers;

use App\Models\DashboardView;
use Illuminate\Http\JsonResponse;
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
            'settings' => ['required', 'array'],
            'order' => ['nullable', 'integer'],
        ]);

        DashboardView::create([
            'website_id' => $validated['website_id'] ?? null,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'settings' => $validated['settings'],
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
            'settings' => ['sometimes', 'array'],
            'order' => ['nullable', 'integer'],
        ]);

        $view->update($validated);

        return back()->with('success', __('msg.dashboard_view_updated'));
    }

    public function destroy(int $viewId): RedirectResponse
    {
        $view = DashboardView::where('user_id', auth()->id())->findOrFail($viewId);
        $view->delete();

        return back()->with('success', __('msg.dashboard_view_deleted'));
    }
}
