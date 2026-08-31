<?php

namespace App\Http\Controllers;

use App\Jobs\Seo\RunSeoAuditJob;
use App\Models\SeoAudit;
use App\Services\Seo\AuditEngine;
use App\Services\Seo\AuditTestRegistry;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SEO 审计控制器（融合方案 §8.1）
 * - 报告三态分享：public 直达 / private 仅作者 / password 凭密码解锁
 * - 公共审计目录：is_public_directory 报告聚合
 */
class SeoAuditController extends Controller
{
    /**
     * 我的审计列表
     */
    public function index(Request $request): View
    {
        $audits = SeoAudit::where('user_id', $request->user()->user_id)
            ->orderByDesc('seo_audit_id')
            ->paginate(15)
            ->withQueryString();

        return view('seo.audits', ['audits' => $audits, 'host' => (string) $request->query('host')]);
    }

    /**
     * 发起审计（登录用户：配额校验后入队）
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'type' => 'nullable|in:single,bulk',
        ]);

        $limit = $this->monthlyLimit($request->user()->getPlanSettings(), 'seo_audits_limit');
        $used = SeoAudit::where('user_id', $request->user()->user_id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($limit >= 0 && $used >= $limit) {
            return back()->withErrors(['url' => __('seo.quota_exceeded')]);
        }

        RunSeoAuditJob::dispatch(
            $validated['url'],
            $request->user()->user_id,
            $validated['type'] ?? 'single',
        );

        return redirect()->route('seo.audits')->with('success', __('seo.audit_queued'));
    }

    /**
     * 报告页（三态访问矩阵）
     */
    public function show(Request $request, SeoAudit $seoAudit): View
    {
        $state = $this->accessState($request, $seoAudit);

        if ($state === 'denied') {
            abort(403, __('seo.report_private'));
        }

        if ($state === 'password') {
            return view('seo.locked', ['audit' => $seoAudit]);
        }

        return view('seo.audit', [
            'audit' => $seoAudit,
            'grouped' => $seoAudit->resultsByCategory(),
            'registry' => app(AuditTestRegistry::class),
        ]);
    }

    /**
     * 密码解锁（写入 session 后回报告页）
     */
    public function unlock(Request $request, SeoAudit $seoAudit)
    {
        $validated = $request->validate(['password' => 'required|string|max:64']);

        if (! $seoAudit->password || ! password_verify($validated['password'], $seoAudit->password)) {
            return back()->withErrors(['password' => __('seo.wrong_password')]);
        }

        $request->session()->put("seo.unlock.{$seoAudit->seo_audit_id}", true);

        return redirect()->route('seo.audits.show', $seoAudit->seo_audit_id);
    }

    /**
     * 访客即时分析（uploader_key 限额）
     */
    public function analyze(Request $request, AuditEngine $engine)
    {
        if (! auth()->check() && ! $this->guestAllowed()) {
            abort(403, __('seo.guest_disabled'));
        }

        $validated = $request->validate(['url' => 'required|url|max:2048']);

        if (auth()->check()) {
            return $this->store($request);
        }

        $key = $request->session()->getId();
        $cap = (int) Settings::get('seo.audits_guest_monthly_limit', 5);

        if ($cap >= 0 && SeoAudit::where('uploader_key', md5($key))
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count() >= $cap) {
            return back()->withErrors(['url' => __('seo.quota_exceeded')]);
        }

        $audit = $engine->run($validated['url'], null, 'single', ['uploader_key' => md5($key)]);

        return redirect()->route('seo.audits.show', $audit->seo_audit_id);
    }

    /**
     * CSV 导出（我的报告）
     */
    public function export(Request $request): StreamedResponse
    {
        $query = SeoAudit::where('user_id', $request->user()->user_id)->orderByDesc('seo_audit_id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBFurl,score,status,major,moderate,minor,created_at\n");

            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->url, $row->score, $row->status, $row->major_issues,
                        $row->moderate_issues, $row->minor_issues, $row->created_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($out);
        }, 'seo-audits-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * 公共审计目录
     */
    public function directory(): View
    {
        $audits = SeoAudit::where('is_public_directory', true)
            ->where('privacy', 'public')
            ->orderByDesc('seo_audit_id')
            ->paginate(20);

        return view('seo.directory', ['audits' => $audits]);
    }

    /**
     * 作者更新分享设置（privacy / 目录收录）
     */
    public function share(Request $request, SeoAudit $seoAudit)
    {
        if ((int) $seoAudit->user_id !== (int) $request->user()->user_id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'privacy' => 'required|in:public,private,password',
            'password' => 'nullable|required_if:privacy,password|string|max:64',
            'is_public_directory' => 'nullable|boolean',
        ]);

        $seoAudit->update([
            'privacy' => $validated['privacy'],
            'password' => ($validated['privacy'] === 'password' && ! empty($validated['password']))
                ? bcrypt($validated['password'])
                : $seoAudit->password,
            'is_public_directory' => (bool) ($validated['is_public_directory'] ?? false),
        ]);

        return back()->with('success', __('seo.share_updated'));
    }

    public function destroy(Request $request, SeoAudit $seoAudit)
    {
        if ((int) $seoAudit->user_id !== (int) $request->user()->user_id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $seoAudit->delete();

        return redirect()->route('seo.audits')->with('success', __('seo.deleted'));
    }

    /**
     * 三态访问矩阵：granted / password / denied
     */
    protected function accessState(Request $request, SeoAudit $audit): string
    {
        $isOwner = auth()->check()
            && ((int) $audit->user_id === (int) $request->user()->user_id || $request->user()->isAdmin());

        if ($isOwner || $audit->privacy === 'public') {
            return 'granted';
        }

        if ($audit->privacy === 'password') {
            return $request->session()->get("seo.unlock.{$audit->seo_audit_id}") ? 'granted' : 'password';
        }

        return 'denied';
    }

    protected function monthlyLimit(array $plan, string $key): int
    {
        return (int) ($plan[$key] ?? -1);
    }

    protected function guestAllowed(): bool
    {
        $enabled = Settings::get('seo.tools_guest_access');

        return $enabled === true || $enabled === 'true' || $enabled === '1';
    }
}
