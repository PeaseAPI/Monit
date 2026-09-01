<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;

/**
 * 前台语言切换（对标原版 settings.languages + language 切换器）
 *
 * 优先级（高→低）：
 * 1. session('locale')（/locale/{code} 路由写入）
 * 2. 浏览器 Accept-Language 自动匹配（main.auto_language_detection_is_enabled）
 * 3. 后台默认语言 main.default_language
 * 4. config('app.locale')
 *
 * 白名单 config('monit.locales')，非法值直接忽略
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locales = (array) config('monit.locales');
        $locale = (string) $request->session()->get('locale', '');

        // 浏览器语言自动检测（main.auto_language_detection_is_enabled，默认开启）
        if ($locale === '' && $this->autoDetectEnabled()) {
            $locale = $this->detectFromAcceptLanguage((string) $request->server('HTTP_ACCEPT_LANGUAGE', ''));
        }

        // 后台默认语言（main.default_language）
        if ($locale === '') {
            $locale = trim((string) Settings::get('main.default_language', ''));
        }

        if ($locale !== '' && array_key_exists($locale, $locales)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    protected function autoDetectEnabled(): bool
    {
        $value = Settings::get('main.auto_language_detection_is_enabled');

        // 默认关闭（保持既有安装行为不变）；后台显式开启才自动匹配浏览器语言
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    /**
     * Accept-Language → 白名单 locale（zh-CN → zh_CN / en-US → en 之类逐级回退）
     */
    protected function detectFromAcceptLanguage(string $header): string
    {
        if ($header === '') {
            return '';
        }

        $locales = (array) config('monit.locales');

        // 解析 q 值并按权重排序
        preg_match_all('/([a-zA-Z-]+)\s*(?:;\s*q\s*=\s*([0-9.]+))?/', $header, $matches, PREG_SET_ORDER);

        $candidates = array_map(fn ($m) => [$m[1], (float) ($m[2] ?? 1.0)], $matches);
        usort($candidates, fn ($a, $b) => $b[1] <=> $a[1]);

        foreach ($candidates as [$tag]) {
            $normalized = str_replace('-', '_', strtolower($tag));

            foreach ($locales as $key => $label) {
                if (strcasecmp((string) $key, $normalized) === 0
                    || str_starts_with(strtolower((string) $key), $normalized.'_')) {
                    return (string) $key;
                }
            }
        }

        return '';
    }
}
