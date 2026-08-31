<?php

namespace App\Http\Controllers;

use App\Models\VisitorsSession;
use App\Models\Website;
use Illuminate\Http\Request;

class SpotlightController extends Controller
{
    /**
     * 聚焦搜索（规格书 §6.2.1：全局快速跳转/搜索）
     * Ctrl+K 风格的全局搜索
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $user = $request->user();

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // 搜索网站
        $websites = Website::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('host', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        foreach ($websites as $website) {
            $results[] = [
                'type' => 'website',
                'id' => $website->website_id,
                'title' => $website->name,
                'subtitle' => $website->host,
                'url' => route('stats.index', $website),
                'icon' => 'globe',
            ];
        }

        // 搜索会话
        $sessions = VisitorsSession::whereHas('website', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('events', fn ($q) => $q->where('path', 'like', "%{$query}%"))
            ->with('website')
            ->limit(5)
            ->get();

        foreach ($sessions as $session) {
            $results[] = [
                'type' => 'session',
                'id' => $session->session_id,
                'title' => $session->events->first()?->path ?? 'Session',
                'subtitle' => $session->website?->host ?? '',
                'url' => route('stats.replays.show', [$session->website, $session->session_id]),
                'icon' => 'play',
            ];
        }

        // 搜索页面导航
        $pages = [
            ['type' => 'nav', 'title' => '仪表盘', 'url' => route('dashboard'), 'icon' => 'home'],
            ['type' => 'nav', 'title' => '网站管理', 'url' => route('websites.index'), 'icon' => 'globe'],
            ['type' => 'nav', 'title' => '账户设置', 'url' => route('account.index'), 'icon' => 'user'],
            ['type' => 'nav', 'title' => '团队管理', 'url' => route('teams.index'), 'icon' => 'users'],
            ['type' => 'nav', 'title' => '套餐', 'url' => route('account.plan'), 'icon' => 'credit-card'],
            ['type' => 'nav', 'title' => '支付记录', 'url' => route('account.payments'), 'icon' => 'receipt'],
        ];

        foreach ($pages as $page) {
            if (str_contains($page['title'], $query)) {
                $results[] = $page;
            }
        }

        return response()->json(['results' => $results]);
    }
}
