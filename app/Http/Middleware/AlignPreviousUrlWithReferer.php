<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST 请求将 session 的 url.previous 对齐为 Referer（表单所在页）。
 *
 * 背景：框架 StartSession 仅在 GET 时写入 url.previous，而 Laravel 的 back()
 * 优先读它——多标签页场景会把它污染成「最后访问的任意站内 GET 页」（如设置页
 * 新标签打开的「详细申请教程 →」帮助文章），保存后 back() 把用户带去无关页面
 * （用户实测：保存 SerpApi Key 被 302 到 serpapi-key 文章页，数据其实已保存）。
 * 本中间件在 POST（表单提交）时把 previousUrl 重置为 Referer——浏览器提交表单
 * 的来源页，即 back() 语义上的正确目标。缺失 Referer 或跨源 Referer 不动作。
 */
class AlignPreviousUrlWithReferer
{
    public function handle(Request $request, Closure $next): Response
    {
        $referer = $request->header('referer');

        if (
            $request->isMethod('POST')
            && is_string($referer)
            && $referer !== ''
            && ! $request->ajax()
            && ! $request->prefetch()
            && ! $request->isPrecognitive()
            && str_starts_with($referer, $request->schemeAndHttpHost().'/')
        ) {
            $request->session()->setPreviousUrl($referer);
        }

        return $next($request);
    }
}
