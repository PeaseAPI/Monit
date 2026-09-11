<?php

namespace App\Http\Controllers;

use App\Models\GoalConversion;
use App\Models\Website;
use App\Models\WebsiteGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 用户中心 - 目标转化管理
 * 规格书 §6.2.2：Goals / GoalCreate / GoalUpdate / GoalDelete
 */
class GoalController extends Controller
{
    /**
     * @return View
     */
    public function index(Request $request, Website $website)
    {
        $goals = $website->goals()
            ->withCount('conversions')
            ->orderBy('goal_id')
            ->get();

        foreach ($goals as $goal) {
            $goal->conversions = $goal->conversions_count;
        }

        return view('stats.goals', compact('website', 'goals'));
    }

    /**
     * @return View
     */
    public function create(Request $request, Website $website)
    {
        return view('stats.goal_create', compact('website'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'website_id' => ['required', 'integer'],
            'key' => ['required', 'string', 'max:256'],
            'type' => ['required', 'in:pageview,scroll,custom'],
            'path' => ['nullable', 'string', 'max:2048'],
            'scroll_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'name' => ['nullable', 'string', 'max:256'],
            'is_enabled' => ['boolean'],
        ]);

        $user = $request->user();
        $website = $user->websites()->where('website_id', (int) $validated['website_id'])->firstOrFail();

        WebsiteGoal::create([
            ...$validated,
            'name' => $validated['name'] ?? $validated['key'],
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        return redirect()->route('stats.goals', ['website' => $website->website_id])
            ->with('success', __('msg.goal_created'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'goal_id' => ['required', 'exists:websites_goals,goal_id'],
            'key' => ['required', 'string', 'max:256'],
            'type' => ['required', 'in:pageview,scroll,custom'],
            'path' => ['nullable', 'string', 'max:2048'],
            'scroll_percentage' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:256'],
            'is_enabled' => ['boolean'],
        ]);

        $goal = WebsiteGoal::query()->where('goal_id', (int) $validated['goal_id'])->firstOrFail();
        $website = Website::where('website_id', $goal->website_id)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();
        $websiteId = $website->website_id;
        $goal->update($validated);

        return redirect()->route('stats.goals', ['website' => $websiteId])
            ->with('success', __('msg.goal_updated'));
    }

    public function delete(Request $request, int $goalId): RedirectResponse
    {
        $goal = WebsiteGoal::findOrFail($goalId);
        $website = Website::where('website_id', $goal->website_id)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();
        $websiteId = $website->website_id;

        // 目标与转化记录同事务删除，避免留下指向已删目标的孤儿转化
        DB::transaction(function () use ($goal, $goalId): void {
            $goal->delete();

            GoalConversion::where('goal_id', $goalId)->delete();
        });

        return redirect()->route('stats.goals', ['website' => $websiteId])
            ->with('success', __('msg.goal_deleted'));
    }
}
