<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Plugin;
use App\Models\Setting;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin API 控制器（规格书 §7：admin-api 路由组）
 */
class AdminApiController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $query = User::query();
        if ($s = $request->input('search')) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        return response()->json($query->orderByDesc('created_at')->paginate(25));
    }

    public function createUser(Request $request): JsonResponse
    {
        $v = $request->validate(['name' => 'required|string|max:128', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:8']);
        $v['password'] = bcrypt($v['password']);
        $v['plan_id'] = $request->input('plan_id', 'free');
        $v['status'] = 1;
        return response()->json(User::create($v), 201);
    }

    public function getUser(int $userId): JsonResponse { return response()->json(User::findOrFail($userId)); }

    public function updateUser(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $v = $request->validate(['name' => 'sometimes|string', 'email' => 'sometimes|email|unique:users,email,'.$userId, 'password' => 'sometimes|string|min:8', 'status' => 'sometimes|in:0,1']);
        if (isset($v['password'])) $v['password'] = bcrypt($v['password']);
        $user->update($v);
        return response()->json($user);
    }

        public function deleteUser(int $userId): JsonResponse { User::findOrFail($userId)->delete(); return response()->json(['deleted' => true]); }

    public function websites(Request $request): JsonResponse
    {
        $query = Website::with('user');
        if ($uid = $request->input('user_id')) $query->where('user_id', $uid);
        return response()->json($query->orderByDesc('created_at')->paginate(25));
    }

    public function getWebsite(int $id): JsonResponse { return response()->json(Website::with('user')->findOrFail($id)); }

    public function updateWebsite(Request $request, int $id): JsonResponse
    {
        $w = Website::findOrFail($id);
        $w->update($request->validate(['is_enabled' => 'sometimes|boolean', 'name' => 'sometimes|string|max:256']));
        return response()->json($w);
    }

    public function deleteWebsite(int $id): JsonResponse { Website::findOrFail($id)->delete(); return response()->json(['deleted' => true]); }

    public function plans(): JsonResponse { return response()->json(Plan::orderBy('order')->get()); }

    public function createPlan(Request $request): JsonResponse
    {
        $v = $request->validate(['plan_id' => 'required|string|unique:plans', 'name' => 'required|string', 'prices' => 'required|array', 'settings' => 'required|array']);
        return response()->json(Plan::create($v), 201);
    }

    public function updatePlan(Request $request, string $planId): JsonResponse
    {
        $p = Plan::findOrFail($planId);
        $p->update($request->validate(['name' => 'sometimes|string', 'prices' => 'sometimes|array', 'settings' => 'sometimes|array', 'is_enabled' => 'sometimes|boolean']));
        return response()->json($p);
    }

    public function deletePlan(string $planId): JsonResponse { Plan::findOrFail($planId)->delete(); return response()->json(['deleted' => true]); }

    public function payments(Request $request): JsonResponse
    {
        $query = Payment::with('user');
        if ($uid = $request->input('user_id')) $query->where('user_id', $uid);
        return response()->json($query->orderByDesc('datetime')->paginate(25));
    }

    public function getPayment(int $id): JsonResponse { return response()->json(Payment::with('user')->findOrFail($id)); }

    public function getSettings(): JsonResponse { return response()->json(Setting::all()->groupBy(fn($i) => explode('.', $i->key)[0])); }

    public function updateSettings(Request $request): JsonResponse
    {
        $v = $request->validate(['settings' => 'required|array']);
        foreach ($v['settings'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (is_array($value) ? json_encode($value) : (string) $value)]);
        }
        \Illuminate\Support\Facades\Cache::forget('monit_settings');
        return response()->json(['updated' => true]);
    }

    public function getStatistics(): JsonResponse
    {
        return response()->json([
            'users' => ['total' => User::count(), 'active' => User::where('status', 1)->count(), 'new_today' => User::whereDate('created_at', today())->count()],
            'websites' => ['total' => Website::count(), 'active' => Website::where('is_enabled', 1)->count()],
            'payments' => ['total' => Payment::where('status', 1)->count(), 'revenue_this_month' => Payment::where('status', 1)->whereMonth('datetime', now()->month)->sum('total_amount')],
        ]);
    }

    public function plugins(): JsonResponse { return response()->json(Plugin::all()); }

    public function updatePlugin(Request $request, int $pluginId): JsonResponse
    {
        $p = Plugin::findOrFail($pluginId);
        $p->update($request->validate(['status' => 'sometimes|integer|in:-1,0,1', 'settings' => 'sometimes|array']));
        return response()->json($p);
    }
}

