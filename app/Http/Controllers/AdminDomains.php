<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 自定义域名管理
 * 规格书 §6.3.2：AdminDomains / AdminDomainCreate / AdminDomainUpdate
 */
class AdminDomains extends Controller
{
    public function index(Request $request)
    {
        $query = Domain::with('user');

        if ($search = $request->query('search')) {
            $query->where('host', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"));
        }

        $domains = $query->orderByDesc('domain_id')->paginate(50);

        return view('admin.domains.index', compact('domains'))->with('adminNav', 'domains');
    }

    public function create()
    {
        $users = User::where('status', 1)->orderBy('name')->limit(1000)->pluck('name', 'user_id');

        return view('admin.domains.create', compact('users'))->with('adminNav', 'domains');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
            'scheme' => ['required', 'in:http,https'],
            'host' => ['required', 'string', 'max:256', 'unique:domains,host'],
            'type' => ['required', 'in:0,1'],
        ]);

        Domain::create([
            ...$validated,
            'is_enabled' => true,
        ]);

        return redirect()->route('admin.domains.index')
            ->with('success', __('msg.domain_created', ['host' => $validated['host']]));
    }

    public function edit(int $domainId)
    {
        $domain = Domain::findOrFail($domainId);
        $users = User::where('status', 1)->orderBy('name')->limit(1000)->pluck('name', 'user_id');

        return view('admin.domains.edit', compact('domain', 'users'))->with('adminNav', 'domains');
    }

    public function update(Request $request, int $domainId): RedirectResponse
    {
        $domain = Domain::findOrFail($domainId);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,user_id'],
            'scheme' => ['required', 'in:http,https'],
            'host' => ['required', 'string', 'max:256', 'unique:domains,host,' . $domainId . ',domain_id'],
            'type' => ['required', 'in:0,1'],
            'is_enabled' => ['boolean'],
        ]);

        $domain->update($validated);

        return redirect()->route('admin.domains.index')
            ->with('success', __('msg.domain_updated', ['host' => $domain->host]));
    }

    public function destroy(int $domainId): RedirectResponse
    {
        $domain = Domain::findOrFail($domainId);
        $host = $domain->host;
        $domain->delete();

        return redirect()->route('admin.domains.index')
            ->with('success', __('msg.domain_deleted', ['host' => $host]));
    }

    public function toggleStatus(int $domainId): RedirectResponse
    {
        $domain = Domain::findOrFail($domainId);
        $domain->update(['is_enabled' => ! $domain->is_enabled]);

        return back()->with('success', __('msg.domain_status_toggled', ['host' => $domain->host, 'status' => $domain->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled')]));
    }
}
