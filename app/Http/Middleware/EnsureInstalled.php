<?php

namespace App\Http\Middleware;

use App\Support\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 安装守卫（全局最前中间件 · 规格书 §15.3/§19）
 *
 * 未安装时一切请求 302 跳转 /install 网页向导——避免「首页直接 500（session/数据库未就绪）」，
 * 用户无需手动执行 key:generate / migrate 等 CLI 初始化。
 * 已安装后 /install 向导自动失效（302 首页）。
 *
 * 注意：作为全局中间件 prepend，先于 web 组的 Session/Cookie 加密执行，
 * 因此未安装时业务中间件不会因数据库未就绪而抛异常。
 */
class EnsureInstalled
{
    /**
     * 未安装时仍可直接访问的路径：
     * 向导自身 + 健康检查 + 像素采集（保持在线）+ 静态资源（向导页样式/图标）
     */
    protected array $allowed = [
        'install', 'install/*',
        'up',
        'pixel-track/*',
        'favicon.ico', 'robots.txt',
        'build/*', 'assets/*', 'docs/*', 'storage/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $isInstallRoute = $request->is('install', 'install/*');

        if (InstallState::installed()) {
            return $isInstallRoute ? redirect('/') : $next($request);
        }

        if ($isInstallRoute || $request->is(...$this->allowed)) {
            return $next($request);
        }

        return redirect('/install');
    }
}
