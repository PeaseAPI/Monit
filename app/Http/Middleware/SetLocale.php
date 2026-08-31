<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 前台语言切换（对标原版 settings.languages + language 切换器）
 *
 * - session('locale') 优先（/locale/{code} 路由写入），否则回落 config('app.locale')
 * - 白名单 config('monit.locales')，非法值直接忽略
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = (string) $request->session()->get('locale', '');

        if ($locale !== '' && array_key_exists($locale, (array) config('monit.locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
