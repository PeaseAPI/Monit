<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/**
 * 通用 API 基类（用于生成 Swagger/OpenAPI 文档）
 * 规格书 §4.2 / §5.30：API 控制器
 */
class ApiController
{
    public static function index()
    {
        $routes = collect(Route::getRoutes())
            ->filter(fn ($r) => str_starts_with($r->uri(), 'api/'))
            ->map(fn ($r) => [
                'uri' => $r->uri(),
                'methods' => $r->methods(),
                'name' => $r->getName(),
            ])
            ->values();

        return response()->json([
            'message' => 'Monit API v1',
            'documentation' => '/api/docs',
            'available_endpoints' => $routes,
        ]);
    }

    /**
     * 验证 API 令牌
     */
    public function validateToken(Request $request)
    {
        $apiKey = $request->bearerToken() ?? $request->query('api_key');

        if (!$apiKey) {
                        return response()->json(['error' => __('msg.missing_api_key')], 401);
        }

        $user = User::where('api_key', $apiKey)->first();
        if (!$user) {
                        return response()->json(['error' => __('msg.invalid_api_key')], 401);
        }

        return response()->json([
            'valid' => true,
            'user_id' => $user->user_id,
            'plan_id' => $user->plan_id,
        ]);
    }

    /**
     * 创建 API 密钥
     */
    public function createApiKey(Request $request)
    {
        $apiKey = Hash::make($request->user()->api_key);

        return response()->json([
                        'message' => __('msg.new_api_key_generated'),
            'api_key' => $apiKey,
        ]);
    }
}