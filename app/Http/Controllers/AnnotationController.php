<?php

namespace App\Http\Controllers;

use App\Models\Annotation;
use App\Models\Website;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 用户中心 - 图表标注
 * 规格书 §6.2.2：Annotations / AnnotationCreate / AnnotationUpdate / AnnotationDelete
 */
class AnnotationController extends Controller
{
    use AuthorizesRequests;

    /**
     * @return View
     */
    public function index(Request $request, Website $website)
    {
        $annotations = $website->annotations()
            ->where('user_id', $this->user()->user_id)
            ->orderByDesc('date')
            ->get();

        return view('stats.annotations', compact('website', 'annotations'));
    }

    /**
     * @return View
     */
    public function create(Request $request, Website $website)
    {
        return view('stats.annotation_create', compact('website'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'website_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:256'],
            'date' => ['required', 'date'],
        ]);

        $user = $this->user();
        $website = $user->websites()->where('website_id', (int) $validated['website_id'])->firstOrFail();

        Annotation::create([
            ...$validated,
            'user_id' => $user->user_id,
        ]);

        return redirect()->route('stats.annotations', ['website' => $website->website_id])
            ->with('success', __('msg.annotation_created'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'annotation_id' => ['required', 'exists:annotations,annotation_id'],
            'name' => ['required', 'string', 'max:256'],
            'date' => ['required', 'date'],
        ]);

        $annotation = Annotation::query()->where('annotation_id', (int) $validated['annotation_id'])->firstOrFail();
        $website = Website::where('website_id', $annotation->website_id)
            ->where('user_id', $this->user()->user_id)
            ->firstOrFail();
        $websiteId = $website->website_id;
        $annotation->update($validated);

        return redirect()->route('stats.annotations', ['website' => $websiteId])
            ->with('success', __('msg.annotation_updated'));
    }

    public function delete(Request $request, int $annotationId): RedirectResponse
    {
        $annotation = Annotation::findOrFail($annotationId);
        $website = Website::where('website_id', $annotation->website_id)
            ->where('user_id', $this->user()->user_id)
            ->firstOrFail();
        $websiteId = $website->website_id;
        $annotation->delete();

        return redirect()->route('stats.annotations', ['website' => $websiteId])
            ->with('success', __('msg.annotation_deleted'));
    }
}
