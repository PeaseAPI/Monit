<?php

namespace App\Http\Controllers;

use App\Models\WebsiteGoal;
use App\Models\GoalConversion;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 用户中心 - 目标转化管理
 * 规格书 §6.2.2：Goals / GoalCreate / GoalUpdate / GoalDelete
 */
class GoalController extends Controller
{
        public function index(Request $request, Website $website)
    {
        $goals = $website->goals()->orderBy('goal_id')->get();

        foreach ($goals as $goal) {
            $goal->conversions = GoalConversion::where('goal_id', $goal->goal_id)->count();
        }

        return view('stats.goals', compact('website', 'goals'));
    }

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
        $website = $user->websites()->findOrFail($validated['website_id']);

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

        $goal = WebsiteGoal::find($validated['goal_id']);
        $websiteId = $goal->website_id;
        $goal->update($validated);

        return redirect()->route('stats.goals', ['website' => $websiteId])
                        ->with('success', __('msg.goal_updated'));
    }

    public function delete(Request $request, int $goalId): RedirectResponse
    {
        $goal = WebsiteGoal::findOrFail($goalId);
        $websiteId = $goal->website_id;
        $goal->delete();

        GoalConversion::where('goal_id', $goalId)->delete();

        return redirect()->route('stats.goals', ['website' => $websiteId])
                        ->with('success', __('msg.goal_deleted'));
    }
}
