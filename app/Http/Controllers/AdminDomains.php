<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 自定义域名管理
 * 规格书 §6.3.2：AdminDomains
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

    public function toggleStatus(int $domainId): RedirectResponse
    {
        $domain = Domain::findOrFail($domainId);
        $domain->update(['is_enabled' => ! $domain->is_enabled]);

                return back()->with('success', __('msg.domain_status_toggled', ['host' => $domain->host, 'status' => $domain->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled')]));
    }
}
