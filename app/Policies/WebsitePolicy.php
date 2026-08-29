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

        // 团队拥有
        return \App\Models\TeamMember::where('team_id', $website->team_id ?? 0)
            ->where('user_id', $user->user_id)
            ->where('status', 1)
            ->exists();
    }

    public function manage(User $user, Website $website): bool
    {
        return $this->own($user, $website);
    }
}