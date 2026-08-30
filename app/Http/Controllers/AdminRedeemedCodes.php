<?php

namespace App\Http\Controllers;

use App\Models\RedeemedCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 已兑换码记录（规格书 §6.3.3：AdminRedeemedCodes）
 */
class AdminRedeemedCodes extends Controller
{
    public function index(Request $request): View
    {
        $query = RedeemedCode::with(['user', 'code'])->orderByDesc('datetime');

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        $redeemedCodes = $query->paginate(25);

        return view('admin.codes.redeemed', compact('redeemedCodes'))->with('adminNav', 'codes');
    }
}
