<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin 中间件 - 仅允许 type=1 的管理员访问
 * 规格书 §6.3：Admin 区域权限
 */
class AdminMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->type !== 1) {
            abort(403, __('msg.forbidden_admin'));
        }

        return $next($request);
    }
}
