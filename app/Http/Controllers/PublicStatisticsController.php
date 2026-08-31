<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

/**
 * Monit 公开统计页
 * 规格书 §6.2.2：/statistics/{key}（可选密码保护）
 */
class PublicStatisticsController extends Controller
{
    /**
     * 公开统计页入口
     */
    public function show(Request $request, string $pixel_key)
    {
        $website = Website::where('pixel_key', $pixel_key)
            ->where('is_enabled', true)
            ->firstOrFail();

        // 检查用户套餐是否允许公开统计
        $user = $website->user;
        if (! $user || $user->status !== 1) {
            abort(404);
        }

        $planSettings = $user->getPlanSettings();
        if (empty($planSettings['websites_public_statistics_is_enabled'])) {
            abort(404);
        }

        // 密码保护检查
        if ($website->settings && ! empty($website->settings['public_statistics_password'])) {
            $sessionKey = 'public_stats_auth_'.$website->website_id;
            $authenticated = $request->session()->get($sessionKey, false);

            if (! $authenticated) {
                return view('stats.public_auth', compact('website'));
            }
        }

        // 统计数据
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.public', [
            'website' => $website,
            'range' => $range,
            'overview' => $stats->overview(),
            'realtime' => $stats->realtime(),
            'series' => $stats->dailySeries(),
            'topPaths' => $stats->breakdown('path'),
            'topReferrers' => $stats->breakdown('referrer_host'),
            'topCountries' => $stats->breakdown('country_code', 8),
            'topDevices' => $stats->breakdown('device_type', 4),
        ]);
    }

    /**
     * 公开统计密码验证
     */
    public function authenticate(Request $request, string $pixel_key)
    {
        $website = Website::where('pixel_key', $pixel_key)->firstOrFail();

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $publicPassword = (string) ($website->settings['public_statistics_password'] ?? '');

        // 恒时比较防时序侧信道；配合路由层 throttle 防爆破
        if ($publicPassword !== '' && hash_equals($publicPassword, (string) $validated['password'])) {
            $request->session()->put('public_stats_auth_'.$website->website_id, true);

            return redirect()->route('statistics.public', ['pixel_key' => $pixel_key]);
        }

        return back()->withErrors(['password' => __('auth.public_stats_password_incorrect')]);
    }
}
