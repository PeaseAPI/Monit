<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Typed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API v1 - 账户资源：当前用户 / 日志 / 支付记录 / 仪表盘视图 / 团队
 * 规格书 §8：/api/user、/api/logs、/api/payments、/api/dashboard-views、/api/teams
 */
class AccountController extends Controller
{
    /* ---------------- 当前用户 ---------------- */

    public function me(): JsonResponse
    {
        $user = $this->user();

        return response()->json([
            'user' => $user->only(['user_id', 'name', 'email', 'plan_id', 'plan_expiration_date', 'api_key', 'datetime']),
            'plan' => $user->plan,
            'plan_settings' => $user->getPlanSettings(),
            'websites_count' => $user->websites()->count(),
        ]);
    }

    /* ---------------- 账户日志 ---------------- */

    public function logs(Request $request): JsonResponse
    {
        return response()->json(
            $this->user()->accountLogs()->orderByDesc('datetime')->limit(100)->get()
        );
    }

    /* ---------------- 支付记录 ---------------- */

    public function payments(Request $request): JsonResponse
    {
        return response()->json(
            $this->user()->payments()->orderByDesc('payment_id')->get()
        );
    }

    /* ---------------- 仪表盘视图 ---------------- */

    public function dashboardViewsIndex(Request $request): JsonResponse
    {
        return response()->json($this->user()->dashboardViews()->orderByDesc('dashboard_view_id')->get());
    }

    public function dashboardViewsStore(Request $request): JsonResponse
    {
        $validated = Typed::arr($request->validate([
            'website_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:256'],
            'settings' => ['nullable', 'array'],
        ]));

        $website = $this->user()->websites()->where('websites.website_id', $validated['website_id'])->firstOrFail();

        $view = $this->user()->dashboardViews()->create([
            'website_id' => $website->website_id,
            'name' => $validated['name'],
            'settings' => $validated['settings'] ?? [],
            'datetime' => now(),
        ]);

        return response()->json(['message' => __('msg.dashboard_view_created'), 'dashboard_view' => $view], 201);
    }

    public function dashboardViewsUpdate(Request $request, int $view): JsonResponse
    {
        $viewModel = $this->user()->dashboardViews()->where('dashboard_view_id', $view)->firstOrFail();

        $validated = Typed::arr($request->validate(['name' => ['sometimes', 'string', 'max:256'],
            'settings' => ['sometimes', 'array'],
        ]));

        $viewModel->update($validated);

        return response()->json(['message' => __('msg.dashboard_view_updated'), 'dashboard_view' => $viewModel]);
    }

    public function dashboardViewsDestroy(Request $request, int $view): JsonResponse
    {
        $this->user()->dashboardViews()->where('dashboard_view_id', $view)->firstOrFail()->delete();

        return response()->json(['message' => __('msg.dashboard_view_deleted')]);
    }

    /* ---------------- 团队 ---------------- */

    public function teamsIndex(Request $request): JsonResponse
    {
        return response()->json(
            $this->user()->teams()->with('members')->orderByDesc('team_id')->get()
        );
    }

    public function teamsStore(Request $request): JsonResponse
    {
        $validated = Typed::arr($request->validate(['name' => ['required', 'string', 'max:256'],
        ]));

        $team = $this->user()->teams()->create([...$validated, 'datetime' => now()]);

        return response()->json(['message' => __('msg.team_created'), 'team' => $team], 201);
    }

    public function teamsShow(Request $request, int $team): JsonResponse
    {
        return response()->json($this->user()->teams()->with('members')->where('team_id', $team)->firstOrFail());
    }

    public function teamsDestroy(Request $request, int $team): JsonResponse
    {
        $this->user()->teams()->where('team_id', $team)->firstOrFail()->delete();

        return response()->json(['message' => __('msg.team_deleted')]);
    }

    /* ---------------- 团队成员 ---------------- */

    public function teamMembersIndex(Request $request, int $team): JsonResponse
    {
        $teamModel = $this->user()->teams()->where('team_id', $team)->firstOrFail();

        return response()->json($teamModel->members()->orderByDesc('team_member_id')->get());
    }

    public function teamMembersDestroy(Request $request, int $team, int $member): JsonResponse
    {
        $teamModel = $this->user()->teams()->where('team_id', $team)->firstOrFail();

        $teamModel->members()->where('team_member_id', $member)->firstOrFail()->delete();

        return response()->json(['message' => __('msg.member_removed')]);
    }

    /* ---------------- 套餐（只读） ---------------- */

    /**
     * 返回可用套餐列表
     * 规格书 §8：/api/plans
     */
    public function plans(): JsonResponse
    {
        return response()->json(
            Plan::where('is_enabled', true)->orderBy('order')->get()
        );
    }
}
