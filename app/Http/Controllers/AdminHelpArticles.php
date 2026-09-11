<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Support\Typed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 管理后台 - 帮助中心文章管理
 */
class AdminHelpArticles extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        $articles = HelpArticle::with('category')->orderBy('order')->orderByDesc('article_id')->paginate(25);

        return view('admin.help_articles.index', compact('articles'))->with('adminNav', 'help-articles');
    }

    /**
     * @return View
     */
    public function create()
    {
        return view('admin.help_articles.form', [
            'article' => new HelpArticle,
            'categories' => HelpCategory::orderBy('order')->get(),
        ])->with('adminNav', 'help-articles');
    }

    public function store(Request $request): RedirectResponse
    {
        HelpArticle::create($this->validated($request) + [
            'user_id' => $this->user()->user_id,
            'views' => 0,
            'datetime' => now(),
        ]);

        return redirect()->route('admin.help-articles.index')->with('success', __('msg.help_article_created'));
    }

    /**
     * @return View
     */
    public function edit(int $articleId)
    {
        return view('admin.help_articles.form', [
            'article' => HelpArticle::findOrFail($articleId),
            'categories' => HelpCategory::orderBy('order')->get(),
        ])->with('adminNav', 'help-articles');
    }

    public function update(Request $request, int $articleId): RedirectResponse
    {
        HelpArticle::findOrFail($articleId)->update($this->validated($request));

        return redirect()->route('admin.help-articles.index')->with('success', __('msg.help_article_updated'));
    }

    public function togglePublish(int $articleId): RedirectResponse
    {
        $article = HelpArticle::findOrFail($articleId);
        $article->update(['is_published' => ! $article->is_published]);

        return back()->with('success', __('msg.help_article_status_toggled'));
    }

    public function destroy(int $articleId): RedirectResponse
    {
        HelpArticle::findOrFail($articleId)->delete();

        return redirect()->route('admin.help-articles.index')->with('success', __('msg.help_article_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return Typed::arr($request->validate([
            'title' => ['required', 'string', 'max:256'],
            'url' => ['nullable', 'string', 'max:256'],
            'category_id' => ['nullable', 'integer', 'exists:help_categories,category_id'],
            'content' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1024'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['boolean'],
        ])) + [
            'url' => Str::slug(Typed::string($request->input('title') ?? '')).'-'.Str::lower(Str::random(6)),
            'is_published' => $request->boolean('is_published', false),
            'order' => Typed::int($request->input('order') ?? 0),
            'category_id' => $request->filled('category_id') ? Typed::int($request->input('category_id')) : null,
        ];
    }
}
