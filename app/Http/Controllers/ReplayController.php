<?php

namespace App\Http\Controllers;

use App\Models\EventChild;
use App\Models\SessionReplay;
use App\Models\Website;
use App\Support\ObjectStorage;
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
     * 读取优先级：Cache → 对象存储（is_offloaded）→ EventChild 回退
     */
    public function events(Request $request, Website $website, int $replayId)
    {
        $replay = SessionReplay::where('website_id', $website->website_id)
            ->findOrFail($replayId);

        $session = $replay->session;

        // 1. 尝试从缓存读取
        $events = [];
        if ($session) {
            $cacheKey = "session_replay_keys_{$session->session_id}";
            $keys = Cache::get($cacheKey, []);

            foreach ($keys as $chunkKey) {
                $chunk = Cache::get($chunkKey);
                if (is_array($chunk)) {
                    $events = array_merge($events, $chunk);
                }
            }
        }

        // 2. 缓存无数据 → 尝试从对象存储读取（is_offloaded）
        if (empty($events) && $replay->is_offloaded) {
            try {
                if (ObjectStorage::isConfigured()) {
                    $storage = ObjectStorage::make();
                    $key = 'replays/'.$replay->website_id.'/'.$replay->session_id.'.json';
                    [$status, $body] = $storage->get($key);

                    if ($status >= 200 && $status < 300 && $body !== '') {
                        $data = json_decode($body, true);
                        if (is_array($data) && isset($data['events']) && is_array($data['events'])) {
                            $events = $data['events'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                logger()->warning('Replay offload read failed', [
                    'replay_id' => $replayId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3. 仍无数据 → 从 EventChild 回退读取（与 OffloadCommand 逻辑一致）
        if (empty($events)) {
            $events = EventChild::where('session_id', $replay->session_id)
                ->orderBy('event_child_id')
                ->get(['type', 'data', 'count', 'date'])
                ->map(fn ($e) => [
                    'type' => $e->type,
                    'data' => $e->data,
                    'count' => $e->count,
                    'date' => (string) $e->date,
                ])
                ->values()
                ->all();
        }

        return response()->json($events);
    }
}
