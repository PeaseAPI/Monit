<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * 安装状态判定（规格书 §15.3 安装器 / §19 部署）
 *
 * 已安装 = storage/installed.lock 存在（网页向导完成时写入）
 *        或 users 表已有 type=1 管理员（CLI migrate+seed 初始化 / 升级部署的兜底）
 *
 * 关联：
 * - 上游：App\Http\Middleware\EnsureInstalled（未安装拦截跳转 /install）
 * - 上游：InstallController（向导可达性判断 / 完成时写锁）
 * - 锁路径可用 MONIT_INSTALL_LOCK 环境变量覆盖（测试隔离用）
 */
class InstallState
{
    public static function lockPath(): string
    {
        return (string) config('monit.install_lock');
    }

    public static function installed(): bool
    {
        if (file_exists(static::lockPath())) {
            return true;
        }

        // 兜底：数据库已有管理员账户视为已安装
        // （MySQL 未配好时连接会抛异常，此时视为未安装）
        try {
            return Schema::hasTable('users') && User::where('type', 1)->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 安装完成：写入锁文件（此后 /install 向导失效）
     */
    public static function complete(): void
    {
        file_put_contents(static::lockPath(), now()->toDateTimeString());
    }
}
