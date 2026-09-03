<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 用户中心 - 自定义域名管理
 * 规格书 §6.2.3：Domains / DomainCreate / DomainUpdate
 */
class DomainController extends Controller
{
    /**
     * host 格式规则（安全审计周期 #17）：仅允许点分域名（ASCII，punycode
     * 形式的 IDN 亦兼容）。此前无格式校验，javascript:alert(1)、
     * http://127.0.0.1/admin、带路径/空格的垃圾可入库并流入
     * whois 监控链路（DomainMonitor::refresh -> whois($host)）。
     * regex 的 i 修饰符放行大写输入，入库前统一 strtolower。
     */
    private const HOST_RULES = [
        'string', 'max:253',
        'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i',
    ];

    public function index(Request $request)
    {
        $domains = $request->user()->domains()->orderBy('domain_id')->get();

        return view('domains.index', compact('domains'));
    }

    public function create()
    {
        return view('domains.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host' => array_merge(['required'], self::HOST_RULES),
        ]);

        $host = strtolower(preg_replace('/^www\./', '', trim($validated['host'])));

        // 同一域名重复添加：友好提示而非 500（unique(user_id, host) 冲突）
        if (Domain::where('user_id', $request->user()->user_id)->where('host', $host)->exists()) {
            return back()->withErrors(['host' => __('msg.domain_exists')])->withInput();
        }

        Domain::create([
            'user_id' => $request->user()->user_id,
            'host' => $host,
            'scheme' => 'https',
            'is_enabled' => true,
            'datetime' => now(),
        ]);

        return redirect()->route('domains.index')
            ->with('success', __('msg.domain_created', ['host' => $host]));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain_id' => ['required', 'exists:domains,domain_id'],
            'host' => array_merge(['sometimes'], self::HOST_RULES),
            'is_enabled' => ['sometimes', 'boolean'],
            'monitor_is_enabled' => ['sometimes', 'boolean'],
        ]);

        // 归属校验：仅允许操作自己的域名（防 IDOR 越权改他人域名/监控开关）
        $domain = $request->user()->domains()->findOrFail($validated['domain_id']);

        $attributes = [];
        if (array_key_exists('host', $validated)) {
            $attributes['host'] = strtolower(preg_replace('/^www\./', '', trim($validated['host'])));
        }
        if (array_key_exists('is_enabled', $validated)) {
            $attributes['is_enabled'] = (bool) $validated['is_enabled'];
        }
        if (array_key_exists('monitor_is_enabled', $validated)) {
            $attributes['monitor_is_enabled'] = (bool) $validated['monitor_is_enabled'];
        }

        $domain->update($attributes);

        return redirect()->route('domains.index')
            ->with('success', __('msg.domain_updated'));
    }

    public function destroy(Request $request, int $domainId): RedirectResponse
    {
        $domain = $request->user()->domains()->findOrFail($domainId);
        $domain->delete();

        return redirect()->route('domains.index')
            ->with('success', __('msg.domain_deleted'));
    }
}
