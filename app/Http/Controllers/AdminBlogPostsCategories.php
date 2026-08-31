<?php

namespace App\Http\Controllers;

use App\Models\BlogPostsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 博客文章分类管理
 * 规格书 §6.3.4 / 附B：AdminBlogPostsCategories、AdminBlogPostsCategoryCreate、AdminBlogPostsCategoryUpdate
 */
class AdminBlogPostsCategories extends Controller
{
    public function index()
    {
        $categories = BlogPostsCategory::with('user')->orderBy('order')->orderByDesc('category_id')->paginate(50);

        return view('admin.blog_posts.categories', compact('categories'))->with('adminNav', 'blog_posts');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        BlogPostsCategory::create($validated + [
            'user_id' => auth()->user()->user_id,
            'datetime' => now(),
        ]);

        return redirect()->route('admin.blog-posts-categories.index')
            ->with('success', __('msg.category_created'));
    }

    public function update(Request $request, int $categoryId): RedirectResponse
    {
        $category = BlogPostsCategory::findOrFail($categoryId);
        $category->update($this->validated($request));

        return redirect()->route('admin.blog-posts-categories.index')
            ->with('success', __('msg.category_updated'));
    }

    public function destroy(int $categoryId): RedirectResponse
    {
        BlogPostsCategory::findOrFail($categoryId)->delete();

        return redirect()->route('admin.blog-posts-categories.index')
            ->with('success', __('msg.category_deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:64'],
            'url' => ['required', 'string', 'max:256', 'regex:/^[a-z0-9-]+$/'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]) + ['order' => (int) ($request->input('order') ?? 0)];
    }
}
