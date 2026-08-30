<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Models\VisitorSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 会话端点（规格书 §8：/api/sessions）
 */
class ApiSessionsController extends Controller
{
    public function index(Request $request, Website $website): JsonResponse
    {
        $this->authorizeWebsite($website);

        $query = VisitorSession::where('website_id', $website->website_id);

        if ($startDate = $request->query('start_date')) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('date', '<=', $endDate);
        }

        $sessions = $query->orderByDesc('date')->paginate(25);

        return response()->json($sessions);
    }

    public function show(Website $website, int $sessionId): JsonResponse
    {
        $this->authorizeWebsite($website);

        $session = VisitorSession::where('website_id', $website->website_id)
            ->with(['events', 'visitor'])
            ->findOrFail($sessionId);

        return response()->json($session);
    }

    protected function authorizeWebsite(Website $website): void
    {
        if ((int) $website->user_id !== (int) auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
