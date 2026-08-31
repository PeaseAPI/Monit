<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Annotation;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 标注端点（规格书 §8：/api/annotations）
 */
class ApiAnnotationsController extends Controller
{
    public function index(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $query = Annotation::where('website_id', $website->website_id);

        if ($startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $annotations = $query->orderByDesc('datetime')->paginate(25);

        return response()->json($annotations);
    }

    public function store(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'datetime' => ['required', 'date'],
            'color' => ['nullable', 'string', 'max:16'],
        ]);

        $annotation = Annotation::create([
            'website_id' => $website->website_id,
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'datetime' => $validated['datetime'],
            'color' => $validated['color'] ?? '#3B82F6',
        ]);

        return response()->json($annotation, 201);
    }

    public function show(Website $website, int $annotationId): JsonResponse
    {
        $this->authorizeWebsite($website);

        $annotation = Annotation::where('website_id', $website->website_id)
            ->findOrFail($annotationId);

        return response()->json($annotation);
    }

    public function update(Request $request, Website $website, int $annotationId): JsonResponse
    {
        $this->authorizeWebsite($website);

        $annotation = Annotation::where('website_id', $website->website_id)
            ->findOrFail($annotationId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:256'],
            'datetime' => ['sometimes', 'date'],
            'color' => ['nullable', 'string', 'max:16'],
        ]);

        $annotation->update($validated);

        return response()->json($annotation);
    }

    public function destroy(Website $website, int $annotationId): JsonResponse
    {
        $this->authorizeWebsite($website);

        $annotation = Annotation::where('website_id', $website->website_id)
            ->findOrFail($annotationId);
        $annotation->delete();

        return response()->json(null, 204);
    }

    protected function authorizeWebsite(Website $website): void
    {
        if ((int) $website->user_id !== (int) auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
