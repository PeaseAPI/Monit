<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 管理后台 - 账户日志查看与下载
 * 规格书 §6.3.5 / 附B：AdminLogs、AdminLog、AdminLogDownload
 */
class AdminLogs extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildQuery($request);

        return view('admin.logs.index', [
            'logs' => $query->paginate(50),
            'types' => AccountLog::distinct()->pluck('type')->filter()->values(),
        ])->with('adminNav', 'logs');
    }

    /**
     * 独立下载路由（规格书 §6.3.5 / 附B：AdminLogDownload）
     * GET /admin/logs/download，支持与列表相同的过滤参数，导出 CSV（上限 10000 条）
     */
    public function download(Request $request): StreamedResponse
    {
        return $this->downloadCsv($this->buildQuery($request)->limit(10000)->get());
    }

    /**
     * 列表/下载共用的过滤查询构造
     */
    private function buildQuery(Request $request)
    {
        return AccountLog::with('user')
            ->when($request->input('user_id'), fn ($q, $v) => $q->where('user_id', (int) $v))
            ->when($request->filled('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->filled('email'), function ($q) use ($request): void {
                $q->whereHas('user', fn ($u) => $u->where('email', 'like', '%'.$request->input('email').'%'));
            })
            ->orderByDesc('log_id');
    }

    private function downloadCsv($logs): StreamedResponse
    {
        $filename = 'monit-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($logs): void {
            $out = fopen('php://output', 'w');
            // BOM 让 Excel 正确识别 UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['log_id', 'user_id', 'email', 'type', 'ip', 'device_type', 'os_name', 'browser_name', 'country_code', 'city_name', 'datetime']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->log_id, $log->user_id, $log->user?->email, $log->type, $log->ip,
                    $log->device_type, $log->os_name, $log->browser_name,
                    $log->country_code, $log->city_name,
                    $log->datetime?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
                }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * 查看单条日志详情
     * 规格书 附B：AdminLog.index
     */
    public function show(int $logId)
    {
        $log = AccountLog::with('user')->findOrFail($logId);

        return view('admin.logs.show', compact('log'))->with('adminNav', 'logs');
    }
}
