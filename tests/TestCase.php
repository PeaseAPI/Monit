<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\PendingCommand;

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

    /**
     * 刷新模型并断言仍存在（fresh() 声明 static|null，测试中模型必然未被删除）
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    protected function freshModel($model)
    {
        $fresh = $model->fresh();
        $this->assertNotNull($fresh);

        return $fresh;
    }

    /**
     * artisan() 声明 PendingCommand|int（直接执行分支），测试中恒为 PendingCommand
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function artisanCmd(string $command, array $parameters = []): PendingCommand
    {
        $cmd = $this->artisan($command, $parameters);
        $this->assertInstanceOf(PendingCommand::class, $cmd);

        return $cmd;
    }
}
