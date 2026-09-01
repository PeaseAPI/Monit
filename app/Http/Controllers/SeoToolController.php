<?php

namespace App\Http\Controllers;

use App\Models\SeoToolUse;
use App\Services\Seo\ToolRunner;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SEO 工具中心控制器（融合方案 §7/§8.1）
 * - 目录页按分类聚合；工具页动态表单 + 结果回显
 * - 配额：登录用户 seo_tools_limit / 访客月度配额（uploader_key）
 */
class SeoToolController extends Controller
{
    public function __construct(protected ToolRunner $runner) {}

    public function index(): View
    {
        // preserveKeys=true：保留 slug 作为键（groupBy 默认重排为数字索引，会把卡片链接渲染成 /tools/0 导致 404）
        $categories = collect($this->runner->catalog())
            ->groupBy(fn (array $meta) => $meta['category'] ?? 'dev', true);

        return view('seo.tools', ['categories' => $categories]);
    }

    public function show(string $slug): View
    {
        $catalog = $this->runner->catalog();

        abort_unless(array_key_exists($slug, $catalog), 404);

        return view('seo.tool', ['slug' => $slug, 'meta' => $catalog[$slug]]);
    }

    /**
     * 执行工具并记录用量
     */
    public function process(Request $request, string $slug)
    {
        $catalog = $this->runner->catalog();

        abort_unless(array_key_exists($slug, $catalog), 404);

        $input = $request->input('input', []);
        $input = is_array($input) ? $input : ['text' => (string) $input];

        $quotaError = $this->checkQuota($request);

        if ($quotaError !== null) {
            return back()->withErrors(['input' => $quotaError])->withInput();
        }

        try {
            $result = $this->runner->run($slug, $input);
        } catch (\Throwable $e) {
            return back()->withErrors(['input' => mb_substr($e->getMessage(), 0, 200)])->withInput();
        }

        $this->recordUse($request, $slug);

        return back()->with([
            'result' => $result,
            'result_slug' => $slug,
        ])->withInput();
    }

    protected function checkQuota(Request $request): ?string
    {
        if ($request->user() !== null) {
            $limit = (int) ($request->user()->getPlanSettings()['seo_tools_limit'] ?? -1);

            if ($limit >= 0 && SeoToolUse::monthlyCount($request->user()->user_id) >= $limit) {
                return __('seo.quota_exceeded');
            }

            return null;
        }

        $cap = (int) Settings::get('seo.tools_guest_monthly_limit', 20);
        $key = md5($request->session()->getId());

        if ($cap >= 0 && SeoToolUse::monthlyCount(null, $key) >= $cap) {
            return __('seo.quota_exceeded');
        }

        return null;
    }

    protected function recordUse(Request $request, string $slug): void
    {
        SeoToolUse::create([
            'user_id' => $request->user()?->user_id,
            'uploader_key' => $request->user() === null ? md5($request->session()->getId()) : null,
            'tool' => $slug,
            'created_at' => now(),
        ]);
    }
}
