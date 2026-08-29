<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 管理后台 - CMS 自定义页面管理
 * 规格书 §6.3.4 / 附B：AdminPages / AdminPageCreate / AdminPageUpdate
 */
class AdminPages extends Controller
{
    public function index()
    {
        $pages = Page::orderByDesc('page_id')->paginate(25);

        return view('admin.pages.index', compact('pages'))->with('adminNav', 'pages');
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page()])->with('adminNav', 'pages');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Page::create([
            ...$validated,
            'user_id' => auth()->user()->user_id,
            'type' => $validated['is_published'] ? 'page' : 'draft',
            'datetime' => now(),
        ]);

        return redirect()->route('admin.pages.index')
                        ->with('success', __('msg.page_created'));
    }

    public function edit(int $pageId)
    {
        $page = Page::findOrFail($pageId);

        return view('admin.pages.form', compact('page'))->with('adminNav', 'pages');
    }

    public function update(Request $request, int $pageId): RedirectResponse
    {
        $page = Page::findOrFail($pageId);
        $validated = $this->validated($request);

        $page->update([
            ...$validated,
            'type' => $validated['is_published'] ? 'page' : 'draft',
        ]);

        return redirect()->route('admin.pages.index')
                        ->with('success', __('msg.page_updated'));
    }

    public function destroy(int $pageId): RedirectResponse
    {
        Page::findOrFail($pageId)->delete();

        return redirect()->route('admin.pages.index')
                        ->with('success', __('msg.page_deleted'));
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:256'],
            'url' => ['nullable', 'string', 'max:256'],
            'content' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1024'],
            'position' => ['nullable', 'in:header,footer,none'],
            'is_published' => ['boolean'],
        ]);

        $validated['url'] = $validated['url'] ?: Str::slug($request->input('title') ?? '') . '-' . Str::lower(Str::random(6));
        $validated['is_published'] = $request->boolean('is_published', false);
        $validated['position'] = $validated['position'] ?? 'none';

        return $validated;
    }
}
