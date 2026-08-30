<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 用户中心 - 团队协作
 * 规格书 §6.2.4：Teams / Team / TeamsAjax / TeamsAssociationsAjax
 */
class TeamController extends Controller
{
    public function index(Request $request)
    {
        $teams = Team::where('user_id', $request->user()->user_id)->get();
        $invitations = TeamMember::where('user_email', $request->user()->email)
            ->where('is_owned', false)
            ->where('status', 0)
            ->with('team')
            ->get();

        return view('teams.index', compact('teams', 'invitations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
        ]);

        Team::create([
            'user_id' => $request->user()->user_id,
            'name' => $validated['name'],
        ]);

        return redirect()->route('teams.index')
                        ->with('success', __('msg.team_created', ['name' => $validated['name']]));
    }

    public function show(Request $request, int $teamId)
    {
        $team = Team::findOrFail($teamId);
        $members = $team->members()->with('user')->get();
        $userWebsites = $request->user()->websites()->get();

        return view('teams.show', compact('team', 'members', 'userWebsites'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', 'exists:teams,team_id'],
            'user_email' => ['required', 'email', 'max:320'],
            'websites_ids' => ['nullable', 'array'],
            'access' => ['nullable', 'array'],
        ]);

        $team = Team::findOrFail($validated['team_id']);

        // 检查是否已存在
        if (TeamMember::where('team_id', $team->team_id)
            ->where('user_email', $validated['user_email'])
            ->exists()) {
                        return back()->withErrors(['user_email' => __('msg.email_already_invited')]);
        }

        TeamMember::create([
            ...$validated,
            'status' => 0, // pending
        ]);

                return back()->with('success', __('msg.invitation_sent', ['email' => $validated['user_email']]));
    }

    public function accept(Request $request, int $memberId): RedirectResponse
    {
        $member = TeamMember::findOrFail($memberId);

        if ($member->user_email !== $request->user()->email) {
            abort(403);
        }

        $member->update([
            'status' => 1,
            'user_id' => $request->user()->user_id,
        ]);

        return redirect()->route('teams.index')
                        ->with('success', __('msg.team_joined', ['name' => $member->team->name]));
    }

    public function remove(Request $request, int $memberId): RedirectResponse
    {
        $member = TeamMember::findOrFail($memberId);
        $teamId = $member->team_id;
        $member->delete();

        return redirect()->route('teams.show', ['teamId' => $teamId])
                        ->with('success', __('msg.member_removed'));
    }

        public function destroy(Request $request, int $teamId): RedirectResponse
    {
        $team = Team::findOrFail($teamId);
        $team->members()->delete();
        $team->delete();

        return redirect()->route('teams.index')
                        ->with('success', __('msg.team_deleted'));
    }

    /**
     * 团队AJAX数据（规格书 §6.2.4：/teams-ajax）
     */
    public function ajax(Request $request)
    {
        $teams = Team::where('user_id', $request->user()->user_id)
            ->withCount('members')
            ->orderByDesc('team_id')
            ->paginate(25);

        return response()->json($teams);
    }

    /**
     * 团队关联AJAX（规格书 §6.2.4：/teams-associations-ajax）
     */
    public function associationsAjax(Request $request)
    {
        $memberId = $request->query('member_id');
        $associations = \App\Models\TeamMemberAssociation::where('team_member_id', $memberId)
            ->with('team', 'website')
            ->get();

        return response()->json($associations);
    }
}
