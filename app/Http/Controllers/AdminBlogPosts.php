<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 管理后台 - 博客文章管理
 * 规格书 §6.3.4 / 附B：AdminBlogPosts / AdminBlogPostCreate / AdminBlogPostUpdate
 */
class AdminBlogPosts extends Controller
{
    public function index()
    {
        $posts = BlogPost::with('category')->orderByDesc('post_id')->paginate(25);

        return view('admin.blog_posts.index', compact('posts'))->with('adminNav', 'blog_posts');
    }

    public function create()
    {
        return view('admin.blog_posts.form', ['post' => new BlogPost])->with('adminNav', 'blog_posts');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        BlogPost::create([
            ...$validated,
            'user_id' => auth()->user()->user_id,
            'type' => $validated['is_published'] ? 'blog' : 'draft',
            'datetime' => now(),
        ]);

        return redirect()->route('admin.blog-posts.index')
            ->with('success', __('msg.blog_post_created'));
    }

    public function edit(int $postId)
    {
        $post = BlogPost::findOrFail($postId);

        return view('admin.blog_posts.form', compact('post'))->with('adminNav', 'blog_posts');
    }

    public function update(Request $request, int $postId): RedirectResponse
    {
        $post = BlogPost::findOrFail($postId);
        $validated = $this->validated($request);

        $post->update([
            ...$validated,
            'type' => $validated['is_published'] ? 'blog' : 'draft',
        ]);

        return redirect()->route('admin.blog-posts.index')
            ->with('success', __('msg.blog_post_updated'));
    }

    public function togglePublish(int $postId): RedirectResponse
    {
        $post = BlogPost::findOrFail($postId);
        $post->update([
            'is_published' => ! $post->is_published,
            'type' => $post->is_published ? 'draft' : 'blog',
        ]);

        return back()->with('success', __('msg.blog_post_status_toggled'));
    }

    public function destroy(int $postId): RedirectResponse
    {
        BlogPost::findOrFail($postId)->delete();

        return redirect()->route('admin.blog-posts.index')
            ->with('success', __('msg.blog_post_deleted'));
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:256'],
            'url' => ['nullable', 'string', 'max:256'],
            'content' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1024'],
            'is_published' => ['boolean'],
        ]) + [
            'url' => Str::slug($request->input('title') ?? '').'-'.Str::lower(Str::random(6)),
            'is_published' => $request->boolean('is_published', false),
        ];
    }
}
