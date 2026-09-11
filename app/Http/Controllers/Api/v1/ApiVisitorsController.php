<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API 访客端点（规格书 §8：/api/visitors）
 */
class ApiVisitorsController extends Controller
{
    public function index(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $query = WebsiteVisitor::where('website_id', $website->website_id);

        if ((bool) $startDate = $request->query('start_date')) {
            $query->where('date', '>=', $startDate);
        }
        if ((bool) $endDate = $request->query('end_date')) {
            $query->where('last_date', '<=', $endDate);
        }
        if ((bool) $country = $request->query('country_code')) {
            $query->where('country_code', $country);
        }
        if ((bool) $device = $request->query('device_type')) {
            $query->where('device_type', $device);
        }

        $visitors = $query->orderByDesc('last_date')->paginate(25);

        return response()->json($visitors);
    }

    public function show(Website $website, int $visitorId): JsonResponse
    {
        $this->authorizeWebsite($website);

        $visitor = WebsiteVisitor::where('website_id', $website->website_id)
            ->findOrFail($visitorId);

        return response()->json($visitor);
    }

    protected function authorizeWebsite(Website $website): void
    {
        if ($website->user_id !== (int) Auth::id() && ! $this->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
