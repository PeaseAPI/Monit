<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 团队成员端点（规格书 §8：/api/team-members）
 */
class ApiTeamMembersController extends Controller
{
    public function index(Request $request, Team $team): JsonResponse
    {
        $this->authorizeTeam($team);

        $members = TeamMember::where('team_id', $team->team_id)
            ->orderByDesc('datetime')
            ->paginate(25);

        return response()->json($members);
    }

    public function show(Team $team, int $memberId): JsonResponse
    {
        $this->authorizeTeam($team);

        $member = TeamMember::where('team_id', $team->team_id)
            ->findOrFail($memberId);

        return response()->json($member);
    }

    public function destroy(Team $team, int $memberId): JsonResponse
    {
        $this->authorizeTeam($team);

        $member = TeamMember::where('team_id', $team->team_id)
            ->findOrFail($memberId);
        $member->delete();

        return response()->json(null, 204);
    }

    protected function authorizeTeam(Team $team): void
    {
        if ((int) $team->user_id !== (int) auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}
