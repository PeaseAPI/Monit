<?php

namespace App\Http\Controllers;

use App\Models\SessionReplay;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Support\Typed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpotlightController extends Controller
{
    /**
     * 聚焦搜索（规格书 §6.2.1：全局快速跳转/搜索）
     * Ctrl+K 风格的全局搜索
     *
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        $query = Typed::string($request->input('q', ''));
        $user = $this->user();

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // 搜索网站（注意：User 主键是 user_id，->id 恒 null 会让搜索恒空）
        $websites = Website::where('user_id', $user->user_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.Typed::string($query).'%')
                    ->orWhere('host', 'like', '%'.Typed::string($query).'%');
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
        $sessions = VisitorSession::whereHas('website', fn ($q) => $q->where('user_id', $user->user_id))
            ->whereHas('events', fn ($q) => $q->where('path', 'like', '%'.Typed::string($query).'%'))
            ->with('website')
            ->limit(5)
            ->get();

        foreach ($sessions as $session) {
            // 回放详情路由参数是 SessionReplay 主键（replay_id），不是 session_id；
            // 无回放记录的会话跳回放列表页
            $replayId = SessionReplay::where('session_id', $session->session_id)->value('replay_id');
            $url = ((bool) $replayId) ? route('stats.replays.show', [$session->website, $replayId])
                : route('stats.replays', $session->website);

            $results[] = [
                'type' => 'session',
                'id' => $session->session_id,
                'title' => $session->events->first()?->path ?? 'Session',
                'subtitle' => $session->website?->host ?? '',
                'url' => $url,
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
            if (str_contains(Typed::string($page['title']), $query)) {
                $results[] = $page;
            }
        }

        return response()->json(['results' => $results]);
    }
}
