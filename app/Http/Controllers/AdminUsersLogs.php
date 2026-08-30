<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 用户日志（规格书 §6.3.2：AdminUsersLogs）
 */
class AdminUsersLogs extends Controller
{
    public function index(Request $request): View
    {
        $query = AccountLog::with('user')->orderByDesc('datetime');

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $logs = $query->paginate(25);

        return view('admin.users.logs', compact('logs'))->with('adminNav', 'users');
    }
}
