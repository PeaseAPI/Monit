<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 邮件/推送广播管理
 * 规格书 §6.3.4 / 附B：AdminBroadcasts / AdminBroadcastCreate / AdminBroadcastUpdate
 * 广播由 monit:process-broadcasts 定时任务（§13）实际发送
 */
class AdminBroadcasts extends Controller
{
    public function index()
    {
        $broadcasts = Broadcast::with('user')->orderByDesc('broadcast_id')->paginate(25);

        return view('admin.broadcasts.index', compact('broadcasts'))->with('adminNav', 'broadcasts');
    }

    public function create()
    {
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();

        return view('admin.broadcasts.form', ['broadcast' => new Broadcast, 'plans' => $plans])->with('adminNav', 'broadcasts');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Broadcast::create([
            ...$validated,
            'user_id' => auth()->user()->user_id,
            'status' => 'draft',
            'datetime' => now(),
        ]);

        return redirect()->route('admin.broadcasts.index')
            ->with('success', __('msg.broadcast_created'));
    }

    public function edit(int $broadcastId)
    {
        $broadcast = Broadcast::findOrFail($broadcastId);
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();

        return view('admin.broadcasts.form', compact('broadcast', 'plans'))->with('adminNav', 'broadcasts');
    }

    public function update(Request $request, int $broadcastId): RedirectResponse
    {
        $broadcast = Broadcast::findOrFail($broadcastId);

        if ($broadcast->status === 'sent') {
            return redirect()->route('admin.broadcasts.index')
                ->with('error', __('msg.broadcast_already_sent'));
        }

        $broadcast->update($this->validated($request));

        return redirect()->route('admin.broadcasts.index')
            ->with('success', __('msg.broadcast_updated'));
    }

    /**
     * 调度发送：置为 pending，由 cron 每分钟处理（规格书 §13.1 broadcasts）
     */
    public function send(int $broadcastId): RedirectResponse
    {
        $broadcast = Broadcast::findOrFail($broadcastId);

        if ($broadcast->status !== 'draft') {
            return back()->with('error', __('msg.broadcast_not_draft'));
        }

        $broadcast->update(['status' => 'pending']);

        return back()->with('success', __('msg.broadcast_scheduled'));
    }

    public function destroy(int $broadcastId): RedirectResponse
    {
        Broadcast::findOrFail($broadcastId)->delete();

        return redirect()->route('admin.broadcasts.index')
            ->with('success', __('msg.broadcast_deleted'));
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:256'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:email,push'],
            'target' => ['required', 'in:all,newsletter,plan'],
            'target_plan_id' => ['nullable', 'required_if:target,plan', 'string', 'max:64'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        if ($validated['target'] !== 'plan') {
            $validated['target_plan_id'] = null;
        }

        return $validated;
    }

    /**
     * 查看广播详情（含发送状态统计）
     * 规格书 §6.3.4：/admin/broadcast-view
     */
    public function show(int $broadcastId)
    {
        $broadcast = Broadcast::with('user')->findOrFail($broadcastId);

        return view('admin.broadcasts.view', compact('broadcast'))->with('adminNav', 'broadcasts');
    }

    /**
     * 复制广播
     * 规格书 附B：AdminBroadcasts.duplicate
     */
    public function duplicate(int $broadcastId): RedirectResponse
    {
        $broadcast = Broadcast::findOrFail($broadcastId);

        $newBroadcast = $broadcast->replicate();
        $newBroadcast->status = 'draft';
        $newBroadcast->title = $broadcast->title.' ('.__('msg.copy').')';
        $newBroadcast->datetime = now();
        $newBroadcast->save();

        return redirect()->route('admin.broadcasts.edit', $newBroadcast->broadcast_id)
            ->with('success', __('msg.broadcast_duplicated'));
    }
}
