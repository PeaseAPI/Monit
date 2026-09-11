<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API 支付记录端点（规格书 §8：/api/payments）
 */
class ApiPaymentsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::where('user_id', Auth::id())
            ->orderByDesc('datetime');

        if ($startDate = $request->query('start_date')) {
            $query->where('datetime', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('datetime', '<=', $endDate);
        }

        $payments = $query->paginate(25);

        return response()->json($payments);
    }
}
