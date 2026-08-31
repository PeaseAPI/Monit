<?php

namespace App\Http\Controllers;

use App\Models\VisitorSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionAjaxController extends Controller
{
    /**
     * 获取单会话详情（规格书 §6.2.2：/session-ajax）
     * AJAX 端点，返回会话事件时间线
     */
    public function show(Request $request, int $sessionId): JsonResponse
    {
        $session = VisitorSession::with(['events', 'visitor', 'website'])
            ->findOrFail($sessionId);

        // 验证权限
        if ($request->user() && $session->website->user_id !== $request->user()->id) {
            // 检查团队成员权限
            $isTeamMember = $session->website->teamMembers()
                ->where('user_id', $request->user()->id)
                ->exists();
            if (! $isTeamMember) {
                abort(403, '无权访问此会话');
            }
        }

        $events = $session->events()->orderBy('date', 'asc')->get();

        return response()->json([
            'session' => $session,
            'visitor' => $session->visitor,
            'events' => $events->map(fn ($e) => [
                'id' => $e->event_id,
                'type' => $e->type,
                'path' => $e->path,
                'title' => $e->title,
                'referrer_host' => $e->referrer_host,
                'referrer_path' => $e->referrer_path,
                'utm_source' => $e->utm_source,
                'utm_medium' => $e->utm_medium,
                'utm_campaign' => $e->utm_campaign,
                'viewport_width' => $e->viewport_width,
                'viewport_height' => $e->viewport_height,
                'date' => $e->date,
            ]),
        ]);
    }
}
