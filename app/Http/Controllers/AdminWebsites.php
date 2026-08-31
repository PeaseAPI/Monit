<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 网站管理
 * 规格书 §6.3.2：AdminWebsites
 */
class AdminWebsites extends Controller
{
    public function index(Request $request)
    {
        $query = Website::with('user');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('host', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"));
        }

        $websites = $query->orderByDesc('website_id')->paginate(50);

        return view('admin.websites.index', compact('websites'))->with('adminNav', 'websites');
    }

    public function toggleStatus(int $websiteId): RedirectResponse
    {
        $website = Website::findOrFail($websiteId);
        $website->update(['is_enabled' => ! $website->is_enabled]);

        return back()->with('success', __('msg.website_status_toggled', ['name' => $website->name, 'status' => $website->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled')]));
    }
}
