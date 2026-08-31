<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 用户偏好设置控制器
 * 规格书 §6.2.5：/account-preferences - 语言、主题、时区、统计默认筛选
 */
class AccountPreferencesController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $preferences = $user->preferences ?? [];

        $timezones = timezone_identifiers_list();

        // 语言以 lang/*.json 文件管理（无 languages 表）
        $languages = collect(glob(lang_path('*.json')))
            ->mapWithKeys(fn (string $file) => [
                basename($file, '.json') => basename($file, '.json'),
            ]);

        return view('account.preferences', compact('user', 'preferences', 'timezones', 'languages'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:64',
            'theme' => 'nullable|in:light,dark,auto',
            'stats_default_range' => 'nullable|in:24h,7d,30d,90d,12m',
        ]);

        $user = auth()->user();
        $preferences = $user->preferences ?? [];

        if ($request->filled('language')) {
            $user->language = $request->input('language');
        }
        if ($request->filled('timezone')) {
            $user->timezone = $request->input('timezone');
        }

        $preferences['theme'] = $request->input('theme', 'auto');
        $preferences['stats_default_range'] = $request->input('stats_default_range', '30d');
        $user->preferences = $preferences;
        $user->save();

        return back()->with('success', __('msg.preferences_saved'));
    }
}
