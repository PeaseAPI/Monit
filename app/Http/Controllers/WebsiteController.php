<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Monit 网站管理 CRUD
 * 依据规格书 §6.2.3：/websites /website-create /website-update
 */
class WebsiteController extends Controller
{
    public function index(Request $request)
    {
        $websites = $request->user()
            ->websites()
            ->withCount([
                'events as events_count' => fn ($q) => $q->whereIn('type', ['landing_page', 'pageview']),
            ])
            ->orderByDesc('website_id')
            ->get();

        return view('websites.index', compact('websites'));
    }

    public function create()
    {
        return view('websites.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // 套餐网站数量限额
        $planSettings = $user->getPlanSettings();
        $limit = $planSettings['websites_limit'] ?? -1;
        if ($limit !== -1 && $user->websites()->count() >= $limit) {
            return back()->withErrors(['url' => "当前套餐最多可创建 {$limit} 个网站，请升级套餐"]);
        }

        $validated = $this->validateWebsite($request);

        $data = $this->parseWebsiteUrl($validated['url']);

        $website = Website::create([
            ...$data,
            'user_id' => $user->user_id,
            'pixel_key' => $this->generatePixelKey(),
            'name' => $validated['name'],
            'tracking_type' => $validated['tracking_type'],
            'bot_exclusion_is_enabled' => $request->boolean('bot_exclusion_is_enabled', true),
            'query_parameters_tracking_is_enabled' => $request->boolean('query_parameters_tracking_is_enabled'),
            'excluded_ips' => $validated['excluded_ips'] ?? null,
            'timezone' => $validated['timezone'],
        ]);

        return redirect()->route('websites.index')
            ->with('success', "网站「{$website->name}」创建成功，请按指引安装像素代码。");
    }

    public function edit(Request $request, int $website)
    {
        $website = $this->findOwnedWebsite($request, $website);

        return view('websites.edit', compact('website'));
    }

    public function update(Request $request, int $website): RedirectResponse
    {
        $website = $this->findOwnedWebsite($request, $website);

        $validated = $this->validateWebsite($request);

        $data = $this->parseWebsiteUrl($validated['url']);

        $website->update([
            ...$data,
            'name' => $validated['name'],
            'tracking_type' => $validated['tracking_type'],
            'is_enabled' => $request->boolean('is_enabled', true),
            'bot_exclusion_is_enabled' => $request->boolean('bot_exclusion_is_enabled', true),
            'query_parameters_tracking_is_enabled' => $request->boolean('query_parameters_tracking_is_enabled'),
            'excluded_ips' => $validated['excluded_ips'] ?? null,
            'timezone' => $validated['timezone'],
        ]);

        return redirect()->route('websites.index')
            ->with('success', "网站「{$website->name}」已更新。");
    }

    public function destroy(Request $request, int $website): RedirectResponse
    {
        $website = $this->findOwnedWebsite($request, $website);
        $name = $website->name;

        $website->delete(); // 关联数据由 FK cascade 删除

        return redirect()->route('websites.index')
            ->with('success', "网站「{$name}」及其全部统计数据已删除。");
    }

    /* ---------------------------------------------------------------------
     | 辅助
     --------------------------------------------------------------------- */

    protected function validateWebsite(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'tracking_type' => ['required', 'in:advanced,lightweight'],
            'excluded_ips' => ['nullable', 'string', 'max:2048'],
            'timezone' => ['required', 'string', 'max:64', 'timezone'],
        ], [
            'name.required' => '请输入网站名称',
            'url.required' => '请输入网站地址',
            'url.url' => '网站地址格式不正确（需以 http:// 或 https:// 开头）',
            'tracking_type.required' => '请选择跟踪模式',
            'tracking_type.in' => '跟踪模式无效',
            'timezone.timezone' => '时区无效',
        ]);
    }

    /**
     * 解析 URL：host 去除 www. 前缀存储（规格书 §3.2）
     *
     * @return array{scheme: string, host: string, path: string}
     */
    protected function parseWebsiteUrl(string $url): array
    {
        $parts = parse_url($url);

        $host = strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host) ?: $host;

        return [
            'scheme' => substr((string) ($parts['scheme'] ?? 'https'), 0, 8),
            'host' => substr($host, 0, 256),
            'path' => substr((string) ($parts['path'] ?? ''), 0, 256),
        ];
    }

    /**
     * 生成 32 位 hex 像素密钥（保证唯一）
     */
    protected function generatePixelKey(): string
    {
        do {
            $key = Str::lower(Str::random(32));
        } while (Website::where('pixel_key', $key)->exists());

        return $key;
    }

    protected function findOwnedWebsite(Request $request, int $websiteId): Website
    {
        $website = $request->user()->websites()->findOrFail($websiteId);
        abort_if(! $website, 404);

        return $website;
    }
}
