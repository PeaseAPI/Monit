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
                        return back()->withErrors(['url' => __('msg.website_limit_reached', ['limit' => $limit])]);
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
                        ->with('success', __('msg.website_created', ['name' => $website->name]));
    }

        public function edit(Request $request, Website $website)
    {
        return view('websites.edit', compact('website'));
    }

    public function update(Request $request, Website $website): RedirectResponse
    {
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
                        ->with('success', __('msg.website_updated', ['name' => $website->name]));
    }

        public function destroy(Request $request, Website $website): RedirectResponse
    {
        $name = $website->name;

        $website->delete(); // Related data deleted by FK cascade

        return redirect()->route('websites.index')
                        ->with('success', __('msg.website_deleted', ['name' => $name]));
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
                        'name.required' => __('validation.website_name_required'),
            'url.required' => __('validation.website_url_required'),
            'url.url' => __('validation.website_url_format'),
            'tracking_type.required' => __('validation.tracking_type_required'),
            'tracking_type.in' => __('validation.tracking_type_invalid'),
            'timezone.timezone' => __('validation.timezone_invalid'),
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

    /**
     * 网站AJAX数据（规格书 §6.2.3：/websites-ajax）
     */
    public function ajax(Request $request)
    {
        $query = Website::where('user_id', $request->user()->user_id);

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('host', 'like', "%{$search}%");
        }

        return response()->json(
            $query->orderByDesc('website_id')->paginate(25)
        );
    }
}
