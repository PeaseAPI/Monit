<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Response;

/**
 * 动态 Favicon 控制器
 * 规格书 §6.1：/favicon 端点 - 动态 favicon 输出
 */
class FaviconController extends Controller
{
    /**
     * 输出动态 favicon（SVG 格式，基于站点首字母）
     */
    public function __invoke(): Response
    {
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
}
