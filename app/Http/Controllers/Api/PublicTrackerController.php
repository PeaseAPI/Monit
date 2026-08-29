<?php

namespace App\Http\Controllers\Api;

use App\Models\Website;
use App\Services\PixelTracker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 公开追踪 API - 无需认证
 * 规格书 §5.1：公开追踪端点
 */
class PublicTrackerController
{
    protected PixelTracker $tracker;

    public function __construct(PixelTracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function track(Request $request): JsonResponse
    {
        // 验证来源
        $host = $request->header('Host');
        if (!$host) {
                        return response()->json(['error' => __('msg.invalid_request_origin')], 400);
        }

        $website = Website::where('host', strtolower($host))->first();
        if (!$website || !$website->is_enabled) {
                        return response()->json(['error' => __('msg.website_not_found')], 404);
        }

        // 解析数据
        $data = $request->validate([
            'url' => 'required|string|max:2048',
            'referrer' => 'nullable|string|max:2048',
            'title' => 'nullable|string|max:256',
            'screen' => 'nullable|string|max:32',
            'user_agent' => 'required|string',
        ]);

        // 存储事件
        $this->tracker->storeEvent($website, [
            'page_url' => $data['url'],
            'referrer' => $data['referrer'] ?? '',
            'page_title' => $data['title'] ?? '',
            'screen' => $data['screen'] ?? '',
            'user_agent' => $data['user_agent'],
            'ip' => $request->ip(),
            'country' => '',
            'device' => '',
            'browser' => '',
            'os' => '',
            'language' => $request->header('Accept-Language') ?? '',
        ]);

        return response()->json(['status' => 'ok']);
    }
}