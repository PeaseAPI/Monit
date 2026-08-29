<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\WebsiteGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API v1 - 目标与标注 资源接口
 * 规格书 §8：/api/goals、/api/annotations
 */
class ResourcesController
{
    /* ---------------- 目标（按网站嵌套） ---------------- */

    public function goalsIndex(Request $request, int $website): JsonResponse
    {
        return response()->json($this->ownWebsite($request, $website)->goals()->orderByDesc('goal_id')->get());
    }

    public function goalsStore(Request $request, int $website): JsonResponse
    {
        $website = $this->ownWebsite($request, $website);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:128'],
            'type' => ['required', 'in:pageview,click,scroll,custom,location'],
            'path' => ['nullable', 'string', 'max:512'],
            'name' => ['required', 'string', 'max:256'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $goal = $website->goals()->create([
            ...$validated,
            'is_enabled' => $validated['is_enabled'] ?? true,
        ]);

        return response()->json(['message' => __('msg.goal_created'), 'goal' => $goal], 201);
    }

    public function goalsShow(Request $request, int $website, int $goal): JsonResponse
    {
        return response()->json(
            WebsiteGoal::where('website_id', $this->ownWebsite($request, $website)->website_id)
                ->where('goal_id', $goal)->firstOrFail()
        );
    }

    public function goalsUpdate(Request $request, int $website, int $goal): JsonResponse
    {
        $goalModel = WebsiteGoal::where('website_id', $this->ownWebsite($request, $website)->website_id)
            ->where('goal_id', $goal)->firstOrFail();

        $validated = $request->validate([
            'key' => ['sometimes', 'string', 'max:128'],
            'type' => ['sometimes', 'in:pageview,click,scroll,custom,location'],
            'path' => ['sometimes', 'nullable', 'string', 'max:512'],
            'name' => ['sometimes', 'string', 'max:256'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        $goalModel->update($validated);

        return response()->json(['message' => __('msg.goal_updated'), 'goal' => $goalModel]);
    }

    public function goalsDestroy(Request $request, int $website, int $goal): JsonResponse
    {
        WebsiteGoal::where('website_id', $this->ownWebsite($request, $website)->website_id)
            ->where('goal_id', $goal)->firstOrFail()->delete();

        return response()->json(['message' => __('msg.goal_deleted')]);
    }

    /* ---------------- 标注（用户级） ---------------- */

    public function annotationsIndex(Request $request): JsonResponse
    {
        return response()->json($request->user()->annotations()->orderByDesc('date')->get());
    }

    public function annotationsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'website_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:256'],
            'date' => ['required', 'date'],
        ]);

        $website = $this->ownWebsite($request, $validated['website_id']);

        $annotation = $request->user()->annotations()->create([
            'website_id' => $website->website_id,
            'name' => $validated['name'],
            'date' => $validated['date'],
        ]);

        return response()->json(['message' => __('msg.annotation_created'), 'annotation' => $annotation], 201);
    }

    public function annotationsUpdate(Request $request, int $annotation): JsonResponse
    {
        $annotationModel = $request->user()->annotations()->where('annotation_id', $annotation)->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:256'],
            'date' => ['sometimes', 'date'],
        ]);

        $annotationModel->update($validated);

        return response()->json(['message' => __('msg.annotation_updated'), 'annotation' => $annotationModel]);
    }

    public function annotationsDestroy(Request $request, int $annotation): JsonResponse
    {
        $request->user()->annotations()->where('annotation_id', $annotation)->firstOrFail()->delete();

        return response()->json(['message' => __('msg.annotation_deleted')]);
    }

    /* ---------------- 辅助 ---------------- */

    protected function ownWebsite(Request $request, int $websiteId)
    {
        return $request->user()->websites()->where('websites.website_id', $websiteId)->firstOrFail();
    }
}
