<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\OutboundClick;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API 出站点击端点（规格书 §8：/api/outbound-clicks）
 */
class ApiOutboundClicksController extends Controller
{
    public function index(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $query = OutboundClick::where('website_id', $website->website_id);

        if ((bool) $startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ((bool) $endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $clicks = $query->orderByDesc('datetime')->paginate(25);

        return response()->json($clicks);
    }

    protected function authorizeWebsite(Website $website): void
    {
        if ($website->user_id !== (int) Auth::id() && ! $this->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
