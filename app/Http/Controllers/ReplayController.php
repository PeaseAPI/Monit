<?php

namespace App\Http\Controllers;

use App\Models\SessionReplay;
use App\Models\Website;
use App\Support\ObjectStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 用户中心 - 会话回放
 * 规格书 §6.2.2：Replays / Replay
 */
class ReplayController extends Controller
{
    /**
     * @return View
     */
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

    /**
     * @return View
     */
    public function show(Request $request, Website $website, int $replayId)
    {
        $replay = SessionReplay::with(['visitor', 'session.events'])
            ->where('website_id', $website->website_id)
            ->findOrFail($replayId);

        return view('stats.replays.show', compact('website', 'replay'));
    }

    /**
     * 返回回放事件 JSON（供 rrweb-player 消费）
     * 读取优先级：DB LONGBLOB（data 列）→ Cache → 对象存储（is_offloaded）→ EventChild 回退
     *
     * @return JsonResponse
     */
    public function events(Request $request, Website $website, int $replayId)
    {
        $replay = SessionReplay::where('website_id', $website->website_id)
            ->findOrFail($replayId);

        // 1. 优先从 DB data 列读取（gzencode 压缩，最可靠）
        $events = [];
        $row = DB::selectOne(
            'SELECT data FROM sessions_replays WHERE replay_id = ?',
            [$replay->replay_id],
        );
        if ($row && $row->data) {
            $decompressed = @gzdecode($row->data);
            if ($decompressed !== false) {
                $data = json_decode($decompressed, true);
                if (is_array($data) && isset($data['events']) && is_array($data['events'])) {
                    $events = $data['events'];
                } elseif (is_array($data) && array_is_list($data)) {
                    // 兼容直接存事件数组的情况
                    $events = $data;
                }
            }
        }

        // 2. DB 无数据 → 尝试从缓存读取
        if (empty($events)) {
            $session = $replay->session;
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
        }

        // 3. 缓存无数据 → 尝试从对象存储读取（is_offloaded）
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

        return response()->json($events);
    }
}
