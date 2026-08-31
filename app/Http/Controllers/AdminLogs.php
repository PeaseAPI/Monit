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
                    $log->log_id, $log->user_id,
                    $this->sanitizeCsvCell($log->user?->email),
                    $this->sanitizeCsvCell($log->type),
                    $this->sanitizeCsvCell($log->ip),
                    $this->sanitizeCsvCell($log->device_type),
                    $this->sanitizeCsvCell($log->os_name),
                    $this->sanitizeCsvCell($log->browser_name),
                    $this->sanitizeCsvCell($log->country_code),
                    $this->sanitizeCsvCell($log->city_name),
                    $log->datetime?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * CSV 公式注入防御：以 = + - @ 制表符/回车开头的单元格前置单引号，
     * 防止导出文件在 Excel/WPS 中被解释为公式执行
     */
    private function sanitizeCsvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (str_starts_with($value, '=') || str_starts_with($value, '+')
            || str_starts_with($value, '-') || str_starts_with($value, '@')
            || str_starts_with($value, "\t") || str_starts_with($value, "\r")) {
            return "'".$value;
        }

        return $value;
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
