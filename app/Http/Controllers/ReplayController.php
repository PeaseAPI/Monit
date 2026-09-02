<?php

namespace App\Http\Controllers;

use App\Models\SessionReplay;
use App\Models\Website;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

/**
 * 用户中心 - 会话回放
 * 规格书 §6.2.2：Replays / Replay
 */
class ReplayController extends Controller
{
    public function index(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);

        $replays = SessionReplay::with(['visitor', 'session'])
            ->where('website_id', $website->website_id)
            ->where('datetime', '>=', now()->subDays($range))
            ->orderByDesc('datetime')
            ->paginate(50);

        return view('stats.replays.index', compact('website', 'replays', 'range'));
    }

        public function show(Request $request, Website $website, int $replayId)
    {
        $replay = SessionReplay::with(['visitor', 'session.events'])
            ->where('website_id', $website->website_id)
            ->findOrFail($replayId);

        return view('stats.replays.show', compact('website', 'replay'));
    }

    /**
     * 返回回放事件 JSON（供 rrweb-player 消费）
     * 从缓存中取出 chunk keys → 逐个取出 chunk 数据 → 合并为 rrweb 事件数组
     */
    public function events(Request $request, Website $website, int $replayId)
    {
        $replay = SessionReplay::where('website_id', $website->website_id)
            ->findOrFail($replayId);

        $session = $replay->session;
        if (! $session) {
            return response()->json([]);
        }

        // 从缓存取出 chunk 索引
        $cacheKey = "session_replay_keys_{$session->session_id}";
                $keys = Cache::get($cacheKey, []);

        // 逐个取出 chunk 数据并合并
        $events = [];
        foreach ($keys as $chunkKey) {
            $chunk = Cache::get($chunkKey);
            if (is_array($chunk)) {
                $events = array_merge($events, $chunk);
            }
        }

        return response()->json($events);
    }
}
