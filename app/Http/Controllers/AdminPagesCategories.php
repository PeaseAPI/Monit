<?php

namespace App\Http\Controllers;

use App\Models\PageCategory;
use App\Support\Typed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 自定义页面分类管理
 * 规格书 §6.3.4 / 附B：AdminPagesCategories、AdminPagesCategoryCreate、AdminPagesCategoryUpdate
 */
class AdminPagesCategories extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        $categories = PageCategory::with('user')->orderBy('order')->orderByDesc('page_category_id')->paginate(50);

        return view('admin.pages.categories', compact('categories'))->with('adminNav', 'pages');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        PageCategory::create($validated + [
            'user_id' => $this->user()->user_id,
            'datetime' => now(),
        ]);

        return redirect()->route('admin.pages-categories.index')
            ->with('success', __('msg.category_created'));
    }

    public function update(Request $request, int $pageCategoryId): RedirectResponse
    {
        $category = PageCategory::findOrFail($pageCategoryId);
        $category->update($this->validated($request));

        return redirect()->route('admin.pages-categories.index')
            ->with('success', __('msg.category_updated'));
    }

    public function destroy(int $pageCategoryId): RedirectResponse
    {
        PageCategory::findOrFail($pageCategoryId)->delete();

        return redirect()->route('admin.pages-categories.index')
            ->with('success', __('msg.category_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return Typed::arr($request->validate([
            'title' => ['required', 'string', 'max:64'],
            'url' => ['required', 'string', 'max:256', 'regex:/^[a-z0-9-]+$/'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ])) + ['order' => Typed::int($request->input('order') ?? 0)];
    }
}
