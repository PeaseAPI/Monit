<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Brand;
use Illuminate\Http\Response;

/**
 * 动态 Favicon 控制器
 * 规格书 §6.1：/favicon 端点 - 动态 favicon 输出
 *
 * 优先级（用户反馈 #4：后台设置的 favicon 未生效）：
 * 1. 后台品牌设置 branding.favicon_url / custom_images.favicon
 *    - 本地路径（/storage/... 或 public 相对路径）→ 直接流式输出文件（正确 Content-Type）
 *    - 完整 URL（http/https）→ 302 重定向
 * 2. 未设置 → 回退首字母 SVG（站点名首字）
 */
class FaviconController extends Controller
{
    /**
     * 输出动态 favicon
     */
    public function __invoke(): Response
    {
        $configured = trim((string) Brand::faviconUrl());

        // 后台已配置：本地文件直接代理输出（浏览器拿到真实内容而非 302），
        // 外链 302 跳转（避免服务器代理外网流量）
        if ($configured !== '' && $configured !== '/favicon.ico') {
            if (preg_match('#^https?://#i', $configured)) {
                return redirect()->away($configured, 302)->header('Cache-Control', 'public, max-age=3600')->send();
            }

            $path = ltrim(parse_url($configured, PHP_URL_PATH) ?: $configured, '/');

            // storage 公开盘 或 public 目录（限定图片扩展，防任意文件读取）
            foreach ([storage_path('app/public/'.urldecode($path)), public_path(urldecode($path))] as $file) {
                if (is_file($file) && preg_match('/\.(ico|png|jpg|jpeg|gif|svg|webp)$/i', $file)) {
                    return (new Response(file_get_contents($file), 200, [
                        'Content-Type' => $this->mimeType($file),
                        'Cache-Control' => 'public, max-age=3600',
                    ]));
                }
            }
        }

        $siteTitle = Setting::where('key', 'main.site_title')->value('value') ?? 'M';
        $letter = mb_strtoupper(mb_substr(trim($siteTitle, '"'), 0, 1));

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
  <rect width="32" height="32" rx="6" fill="#6366f1"/>
  <text x="16" y="23" font-family="system-ui,sans-serif" font-size="20" font-weight="bold" fill="white" text-anchor="middle">{$letter}</text>
</svg>
SVG;

        return new Response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    protected function mimeType(string $file): string
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
