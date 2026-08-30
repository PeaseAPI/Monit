<?php

namespace App\Http\Controllers\Api;

use App\Models\Website;
use App\Services\PixelTracker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * 公开追踪 API（规格书 §5.1：POST /api/v1/public/track，API Key 门控）
 *
 * M23 修复：原先调用不存在的 PixelTracker::storeEvent()（必然 500）。
 * 现统一走 PixelTracker::handle() 像素采集管线——复用前置校验/地理/UA 解析/
 * 用量配额逻辑，与 /pixel-track/{key} 端点行为完全一致（关联：app/Services/PixelTracker.php）。
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

        // 组装为像素协议载荷（规格书 §4.1 data.type=data 的标准结构），
        // 注入 request 后复用 PixelTracker::handle 全链路处理。
        $request->merge(['data' => [
            'type' => 'pageview',
            'url' => $data['url'],
            'referrer' => $data['referrer'] ?? '',
            'title' => $data['title'] ?? '',
            'resolution' => $this->parseResolution($data['screen'] ?? ''),
        ]]);

        // API 客户端可能显式传 user_agent；PixelTracker 内部读取当前请求 UA，
        // 因此这里把显式传入的 UA 同步到请求头，保证解析结果一致。
        if (!empty($data['user_agent'])) {
            $request->headers->set('User-Agent', (string) $data['user_agent']);
        }

        $this->tracker->onSkip(fn (string $reason) => null);
        $this->tracker->handle($website, $request);

        return response()->json(['status' => 'ok']);
    }

    /**
     * "1920x1080" → ['width' => 1920, 'height' => 1080]
     */
    protected function parseResolution(string $screen): array
    {
        if (preg_match('/^(\d+)[xX*](\d+)$/', trim($screen), $m)) {
            return ['width' => (int) $m[1], 'height' => (int) $m[2]];
        }

        return [];
    }
}
