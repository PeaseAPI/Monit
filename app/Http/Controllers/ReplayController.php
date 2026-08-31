<?php

namespace App\Http\Controllers;

use App\Models\SessionReplay;
use App\Models\Website;
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
}
