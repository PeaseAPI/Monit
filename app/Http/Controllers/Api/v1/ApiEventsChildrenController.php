<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Models\EventChild;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 事件子项端点（规格书 §8：/api/events-children）
 */
class ApiEventsChildrenController extends Controller
{
    public function index(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $query = EventChild::where('website_id', $website->website_id);

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $events = $query->orderByDesc('datetime')->paginate(25);

        return response()->json($events);
    }

    protected function authorizeWebsite(Website $website): void
    {
        if ((int) $website->user_id !== (int) auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
