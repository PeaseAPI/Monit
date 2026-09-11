<?php

namespace App\Http\Controllers;

use App\Models\User;

abstract class Controller
{
    /**
     * 当前认证用户（规格书 §6.2：登录后访问的页面均挂在 auth 中间件下，
     * 未认证时此处 401 与中间件行为一致，仅供子类调用以便静态分析窄化）
     */
    protected function user(): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_if($user === null, 401);

        return $user;
    }
}
