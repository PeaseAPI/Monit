<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 平台级响应头/重定向（对标原版 main 组安全与 SEO 头设置）
 *
 * 消费设置：
 * - main.force_https：非 HTTPS 请求 301 跳转（本地/测试环境自动豁免）
 * - main.iframe_is_enabled：关闭时输出 X-Frame-Options: DENY（防点击劫持）
 * - main.ai_crawlers_is_enabled：开启时输出 X-Robots-Tag: noai, noimageai（拒绝 AI 爬取）
 * - main.referrer_policy：输出 Referrer-Policy 头
 */
class ApplyPlatformHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // 入站一次性读取全部设置（若出站再读，admin 清缓存后响应链路会立即重建缓存）
        $forceHttps = $this->forceHttps();
        $iframeAllowed = $this->iframeAllowed();
        $aiBlocked = $this->aiCrawlersBlocked();
        $referrerPolicy = $this->referrerPolicy();

        // 强制 HTTPS（app.debug 或本地环境豁免，避免开发环境死循环）
        if ($forceHttps && ! $request->secure() && ! $this->isLocalLike($request)) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        if (! $iframeAllowed) {
            $response->headers->set('X-Frame-Options', 'DENY');
        }

        if (! $response->headers->has('X-Robots-Tag') && $aiBlocked) {
            $response->headers->set('X-Robots-Tag', 'noai, noimageai');
        }

        if ($referrerPolicy) {
            $response->headers->set('Referrer-Policy', $referrerPolicy);
        }

        return $response;
    }

    /* ------------------------------------------------------------------ */

    protected function forceHttps(): bool
    {
        return self::on(Settings::get('main.force_https'));
    }

    protected function iframeAllowed(): bool
    {
        // 默认允许（原版默认 true），显式关闭才收紧
        return Settings::get('main.iframe_is_enabled') === null
            || self::on(Settings::get('main.iframe_is_enabled'));
    }

    /**
     * AI 爬虫是否被拒绝（main.ai_crawlers_is_enabled=false 时输出 noai 头；默认允许）
     */
    protected function aiCrawlersBlocked(): bool
    {
        $value = Settings::get('main.ai_crawlers_is_enabled');

        return $value !== null && ! self::on($value);
    }

    protected function referrerPolicy(): ?string
    {
        $policy = trim((string) Settings::get('main.referrer_policy', ''));

        if ($policy === '') {
            return null;
        }

        // 白名单防头注入（仅允许标准策略 token）
        return preg_match('/^[a-zA-Z0-9-]+$/', $policy) ? $policy : null;
    }

    protected function isLocalLike(Request $request): bool
    {
        return config('app.debug') === true
            || in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }

    protected static function on(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
