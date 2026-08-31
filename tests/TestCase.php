<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 默认视为「已安装」：写入安装锁（phpunit.xml MONIT_INSTALL_LOCK 指向 tests/tmp/），
        // 使 EnsureInstalled 全局中间件不拦截现有用例；InstallWizardTest 自行删除该锁模拟全新实例
        $this->touchInstallLock();
    }

    protected function tearDown(): void
    {
        // 清理安装锁，避免向导用例的「未安装」状态泄漏到其他用例
        $lock = config('monit.install_lock');
        if (is_string($lock) && file_exists($lock)) {
            @unlink($lock);
        }

        parent::tearDown();
    }

    protected function touchInstallLock(): void
    {
        $lock = config('monit.install_lock');

        if (is_string($lock) && ! file_exists($lock)) {
            @mkdir(dirname($lock), 0777, true);
            touch($lock);
        }
    }
}
