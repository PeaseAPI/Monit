<?php

namespace App\Http\Controllers;

use App\Models\Annotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 全平台标注管理
 * 规格书 §6.3.5 / 附B：AdminAnnotations
 */
class AdminAnnotations extends Controller
{
    public function index(Request $request)
    {
        $annotations = Annotation::with('website')
            ->when($request->input('search'), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderByDesc('annotation_id')
            ->paginate(25);

        return view('admin.annotations.index', compact('annotations'))->with('adminNav', 'annotations');
    }

    public function destroy(int $annotationId): RedirectResponse
    {
        Annotation::findOrFail($annotationId)->delete();

        return redirect()->route('admin.annotations.index')
                        ->with('success', __('msg.annotation_deleted'));
    }
}
