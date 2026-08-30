<?php

namespace App\Http\Controllers;

use App\Models\SessionReplay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 平台级会话回放管理
 * 规格书 §6.3.5 / 附B：AdminReplays
 */
class AdminReplays extends Controller
{
    public function index(Request $request)
    {
        $replays = SessionReplay::with(['website', 'session'])
            ->when($request->input('website_id'), fn ($q, $v) => $q->where('website_id', (int) $v))
            ->orderByDesc('replay_id')
            ->paginate(25);

        return view('admin.replays.index', compact('replays'))->with('adminNav', 'replays');
    }

    public function destroy(int $replayId): RedirectResponse
    {
        SessionReplay::findOrFail($replayId)->delete();

        return redirect()->route('admin.replays.index')
                        ->with('success', __('msg.replay_deleted'));
    }
}
