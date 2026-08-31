<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\SessionReplay;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 会话回放端点（规格书 §8：/api/replays）
 */
class ApiReplaysController extends Controller
{
    public function index(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $query = SessionReplay::where('website_id', $website->website_id);

        if ($startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $replays = $query->orderByDesc('datetime')->paginate(25);

        return response()->json($replays);
    }

    public function show(Website $website, int $replayId): JsonResponse
    {
        $this->authorizeWebsite($website);

        $replay = SessionReplay::where('website_id', $website->website_id)
            ->findOrFail($replayId);

        return response()->json($replay);
    }

    protected function authorizeWebsite(Website $website): void
    {
        if ((int) $website->user_id !== (int) auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
