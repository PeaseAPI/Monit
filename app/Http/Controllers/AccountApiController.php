<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 账户 API Key 管理控制器
 * 规格书 §6.2.5：/account-api - API Key 生成与管理
 */
class AccountApiController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('account.api', compact('user'));
    }

    public function regenerate(): RedirectResponse
    {
        $user = auth()->user();
        $user->forceFill(['api_key' => Str::random(60)])->save();

        return back()->with('success', __('msg.api_key_regenerated'));
    }

    public function revoke(): RedirectResponse
    {
        $user = auth()->user();
        $user->forceFill(['api_key' => null])->save();

        return back()->with('success', __('msg.api_key_revoked'));
    }
}
