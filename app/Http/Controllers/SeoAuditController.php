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
     * 支持四种审计类型：single / sitemap / bulk / html
     */
    public function store(Request $request)
    {
        $type = $request->input('type', 'single');
        $rules = ['type' => 'required|in:single,sitemap,bulk,html'];

        // 根据审计类型动态校验
        match ($type) {
            'sitemap' => $rules['url'] = 'required|url|max:2048',
            'bulk'    => $rules['urls'] = 'required|string|max:65535',
            'html'    => $rules['url'] = 'required|url|max:2048',
            default   => $rules['url'] = 'required|url|max:2048',
        };

        $validated = $request->validate($rules);

        $plan = $request->user()->getPlanSettings();
        $limit = $this->monthlyLimit($plan, 'seo_audits_limit');
        $used = SeoAudit::where('user_id', $request->user()->user_id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($limit >= 0 && $used >= $limit) {
            return back()->withErrors(['url' => __('seo.quota_exceeded')]);
        }

        // Sitemap / Bulk：多 URL 入队
        if ($type === 'sitemap') {
            $bulkLimit = $this->monthlyLimit($plan, 'seo_bulk_limit') ?: 50;
            dispatch(function () use ($validated, $request, $bulkLimit) {
                $urls = app(\App\Services\Seo\SitemapMonitor::class)
                    ->fetch($validated['url'])['urls'] ?? [];
                $urls = array_slice($urls, 0, $bulkLimit);
                foreach ($urls as $url) {
                    RunSeoAuditJob::dispatch($url, $request->user()->user_id, 'single');
                }
            });

            return redirect()->route('seo.audits')->with('success', __('seo.audit_queued'));
        }

        if ($type === 'bulk') {
            $bulkLimit = $this->monthlyLimit($plan, 'seo_bulk_limit') ?: 50;
            $urls = collect(preg_split('/\R/', $validated['urls']))
                ->map(fn (string $u) => static::ensureScheme(trim($u)))
                ->filter(fn (string $u) => filter_var($u, FILTER_VALIDATE_URL))
                ->take($bulkLimit);

            foreach ($urls as $url) {
                RunSeoAuditJob::dispatch($url, $request->user()->user_id, 'single');
            }

            return redirect()->route('seo.audits')->with('success', __('seo.audit_queued'));
        }

        if ($type === 'html') {
            $html = $request->input('html', '');
            if (trim($html) === '') {
                return back()->withErrors(['html' => __('seo.html_required')])->withInput();
            }

            RunSeoAuditJob::dispatch(
                $validated['url'],
                $request->user()->user_id,
                'html',
                ['html' => $html],
            );

            return redirect()->route('seo.audits')->with('success', __('seo.audit_queued'));
        }

        // Single
        $url = static::ensureScheme($validated['url']);
        RunSeoAuditJob::dispatch($url, $request->user()->user_id, 'single');

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
        $user = $request->user();

        if ($user === null && ! $this->guestAllowed()) {
            abort(403, __('seo.guest_disabled'));
        }

        $url = static::ensureScheme($request->input('url', ''));
        $request->merge(['url' => $url]);

        $validated = $request->validate(['url' => 'required|url|max:2048']);

        if ($user !== null) {
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
     * AI 审计摘要（异步队列生成，刷新后查看）
     */
    public function aiSummary(Request $request, SeoAudit $seoAudit)
    {
        if ((int) $seoAudit->user_id !== (int) $request->user()->user_id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        if (! \App\Services\Ai\AiService::isEnabled()) {
            return back()->withErrors(['ai' => __('seo.ai_not_enabled')]);
        }

        \App\Jobs\Seo\SeoAiSummaryJob::dispatch($seoAudit, $request->user());

        return back()->with('success', __('seo.ai_summary_queued'));
    }

    /**
     * 三态访问矩阵：granted / password / denied
     */
    protected function accessState(Request $request, SeoAudit $audit): string
    {
        $user = $request->user();
        $isOwner = $user !== null
            && ((int) $audit->user_id === (int) $user->user_id || $user->isAdmin());

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
        return filter_var(Settings::get('seo.tools_guest_access'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 自动为缺少协议的 URL 补上 https://
     * 例：example.com → https://example.com
     */
    protected static function ensureScheme(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }
}
