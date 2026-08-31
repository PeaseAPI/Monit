<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;

/**
 * 管理后台 - 团队与成员管理
 * 规格书 §6.3.2 / 附B：AdminTeams、AdminTeamMembers
 */
class AdminTeams extends Controller
{
    public function index()
    {
        $teams = Team::with('owner')
            ->withCount('members')
            ->orderByDesc('team_id')
            ->paginate(25);

        return view('admin.teams.index', compact('teams'))->with('adminNav', 'teams');
    }

    public function members(int $teamId)
    {
        $team = Team::with('owner')->findOrFail($teamId);
        $members = $team->members()->orderByDesc('team_member_id')->paginate(50);

        return view('admin.teams.members', compact('team', 'members'))->with('adminNav', 'teams');
    }

    public function destroy(int $teamId): RedirectResponse
    {
        $team = Team::findOrFail($teamId);
        $team->members()->delete();
        $team->delete();

        return redirect()->route('admin.teams.index')
            ->with('success', __('msg.team_deleted'));
    }

    public function destroyMember(int $teamId, int $memberId): RedirectResponse
    {
        TeamMember::where('team_id', $teamId)->where('team_member_id', $memberId)->firstOrFail()->delete();

        return redirect()->route('admin.teams.members', $teamId)
            ->with('success', __('msg.team_member_removed'));
    }
}
