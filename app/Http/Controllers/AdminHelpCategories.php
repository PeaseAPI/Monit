<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 管理后台 - 帮助中心分类管理
 */
class AdminHelpCategories extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        $categories = HelpCategory::with('user')->orderBy('order')->orderByDesc('category_id')->paginate(50);

        return view('admin.help.categories', compact('categories'))->with('adminNav', 'help-categories');
    }

    public function store(Request $request): RedirectResponse
    {
        HelpCategory::create($this->validated($request) + [
            'user_id' => $request->user()->user_id,
            'datetime' => now(),
        ]);

        return redirect()->route('admin.help-categories.index')->with('success', __('msg.category_created'));
    }

    public function update(Request $request, int $categoryId): RedirectResponse
    {
        HelpCategory::findOrFail($categoryId)->update($this->validated($request));

        return redirect()->route('admin.help-categories.index')->with('success', __('msg.category_updated'));
    }

    public function destroy(int $categoryId): RedirectResponse
    {
        // 文章归类置空与分类删除同事务，避免留下悬空分类归属
        DB::transaction(function () use ($categoryId): void {
            // 分类下文章的 category_id 置空（文章保留为未分类）
            HelpArticle::where('category_id', $categoryId)->update(['category_id' => null]);
            HelpCategory::findOrFail($categoryId)->delete();
        });

        return redirect()->route('admin.help-categories.index')->with('success', __('msg.category_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:64'],
            'url' => ['required', 'string', 'max:256', 'regex:/^[a-z0-9-]+$/'],
            'icon' => ['nullable', 'string', 'max:32'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]) + ['order' => (int) ($request->input('order') ?? 0), 'icon' => (string) ($request->input('icon') ?: 'book')];
    }
}
