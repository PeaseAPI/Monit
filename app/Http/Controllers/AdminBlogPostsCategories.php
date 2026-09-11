<?php

namespace App\Http\Controllers;

use App\Models\BlogPostsCategory;
use App\Support\Typed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 博客文章分类管理
 * 规格书 §6.3.4 / 附B：AdminBlogPostsCategories、AdminBlogPostsCategoryCreate、AdminBlogPostsCategoryUpdate
 */
class AdminBlogPostsCategories extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        $categories = BlogPostsCategory::with('user')->orderBy('order')->orderByDesc('category_id')->paginate(50);

        return view('admin.blog_posts.categories', compact('categories'))->with('adminNav', 'blog_posts');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        BlogPostsCategory::create($validated + [
            'user_id' => $this->user()->user_id,
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
