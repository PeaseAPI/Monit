<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\GoalConversion;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 目标转化端点（规格书 §8：/api/goals-conversions）
 */
class ApiGoalsConversionsController extends Controller
{
    public function index(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $query = GoalConversion::where('website_id', $website->website_id);

        if ($goalId = $request->query('goal_id')) {
            $query->where('goal_id', $goalId);
        }
        if ($startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $conversions = $query->orderByDesc('datetime')->paginate(25);

        return response()->json($conversions);
    }

    public function show(Website $website, int $conversionId): JsonResponse
    {
        $this->authorizeWebsite($website);

        $conversion = GoalConversion::where('website_id', $website->website_id)
            ->findOrFail($conversionId);

        return response()->json($conversion);
    }

    protected function authorizeWebsite(Website $website): void
    {
        if ((int) $website->user_id !== (int) auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
