<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AccountLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API 操作日志端点（规格书 §8：/api/logs）
 */
class ApiLogsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AccountLog::where('user_id', Auth::id())
            ->orderByDesc('datetime');

        if ((bool) $type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ((bool) $startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ((bool) $endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $logs = $query->paginate(25);

        return response()->json($logs);
    }
}
