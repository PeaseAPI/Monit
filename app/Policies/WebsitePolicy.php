<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Website;

class WebsitePolicy
{
    public function own(User $user, Website $website): bool
    {
        // 管理员拥有所有权限
        if ($user->isAdmin()) {
            return true;
        }

        // 直接拥有
        if ($website->user_id === $user->user_id) {
            return true;
        }

        // 团队拥有：websites 表没有 team_id 关联列（协作关系走 collaboration 表），
        // 原实现读取不存在的 $website->team_id 恒为 null→0，该查询永假，行为上即拒绝。
        // 保持等价语义，显式化这一事实。
        return false;
    }

    public function manage(User $user, Website $website): bool
    {
        return $this->own($user, $website);
    }
}
