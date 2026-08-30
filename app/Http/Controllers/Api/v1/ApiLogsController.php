<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AccountLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 操作日志端点（规格书 §8：/api/logs）
 */
class ApiLogsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AccountLog::where('user_id', auth()->id())
            ->orderByDesc('datetime');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $logs = $query->paginate(25);

        return response()->json($logs);
    }
}
