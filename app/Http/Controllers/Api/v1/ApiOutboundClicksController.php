<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Models\OutboundClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 出站点击端点（规格书 §8：/api/outbound-clicks）
 */
class ApiOutboundClicksController extends Controller
{
    public function index(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $query = OutboundClick::where('website_id', $website->website_id);

        if ($startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $clicks = $query->orderByDesc('datetime')->paginate(25);

        return response()->json($clicks);
    }

    protected function authorizeWebsite(Website $website): void
    {
        if ((int) $website->user_id !== (int) auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
