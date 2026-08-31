<?php

namespace App\Http\Controllers;

use App\Models\Annotation;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 用户中心 - 图表标注
 * 规格书 §6.2.2：Annotations / AnnotationCreate / AnnotationUpdate / AnnotationDelete
 */
class AnnotationController extends Controller
{
    public function index(Request $request, Website $website)
    {
        $annotations = $website->annotations()
            ->where('user_id', $request->user()->user_id)
            ->orderByDesc('date')
            ->get();

        return view('stats.annotations', compact('website', 'annotations'));
    }

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

        $user = $request->user();
        $website = $user->websites()->findOrFail($validated['website_id']);

        Annotation::create([
            ...$validated,
            'user_id' => $user->user_id,
        ]);

        return redirect()->route('annotations.index', ['website' => $website->website_id])
            ->with('success', __('msg.annotation_created'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'annotation_id' => ['required', 'exists:annotations,annotation_id'],
            'name' => ['required', 'string', 'max:256'],
            'date' => ['required', 'date'],
        ]);

        $annotation = Annotation::find($validated['annotation_id']);
        $websiteId = $annotation->website_id;
        $annotation->update($validated);

        return redirect()->route('annotations.index', ['website' => $websiteId])
            ->with('success', __('msg.annotation_updated'));
    }

    public function delete(Request $request, int $annotationId): RedirectResponse
    {
        $annotation = Annotation::findOrFail($annotationId);
        $websiteId = $annotation->website_id;
        $annotation->delete();

        return redirect()->route('annotations.index', ['website' => $websiteId])
            ->with('success', __('msg.annotation_deleted'));
    }

    public function destroy(Request $request, Website $website, Annotation $annotation): RedirectResponse
    {
        $this->authorize('own', $website);
        $annotation->delete();

        return redirect()->route('annotations.index', $website)
            ->with('success', __('msg.annotation_deleted'));
    }
}
