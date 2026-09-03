<?php

namespace App\Http\Controllers;

use App\Models\TeamMemberAssociation;
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

        // 验证权限（安全审计周期 #15 修复双重缺陷）：
        // - User 主键为 user_id，此前误用 ->id（恒为 null）导致所有权比对永假
        // - Website::teamMembers() 关系并不存在，触发 BadMethodCall 500；
        //   改为查询 team_member_associations（成员↔网站关联网，周期 #12
        //   起由 TeamMember 级联清理维护，与团队成员数据流一致）
        $user = $request->user();
        if ($user && (int) $session->website->user_id !== (int) $user->user_id) {
            $isTeamMember = TeamMemberAssociation::query()
                ->where('website_id', $session->website->website_id)
                ->whereHas('member', fn ($q) => $q->where('user_id', $user->user_id))
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
