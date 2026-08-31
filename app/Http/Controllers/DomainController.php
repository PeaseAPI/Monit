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
            'host' => ['required', 'string', 'max:256'],
        ]);

        $host = strtolower(preg_replace('/^www\./', '', trim($validated['host'])));

        Domain::create([
            'user_id' => $request->user()->user_id,
            'host' => $host,
            'scheme' => 'https',
            'is_enabled' => true,
        ]);

        return redirect()->route('domains.index')
            ->with('success', __('msg.domain_created', ['host' => $host]));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain_id' => ['required', 'exists:domains,domain_id'],
            'host' => ['required', 'string', 'max:256'],
            'is_enabled' => ['boolean'],
        ]);

        $domain = Domain::find($validated['domain_id']);
        $host = strtolower(preg_replace('/^www\./', '', trim($validated['host'])));
        $domain->update(['host' => $host, 'is_enabled' => $request->boolean('is_enabled', true)]);

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
