<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EnvWriter;
use App\Support\InstallState;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 安装向导安全回归测试（安全审计轮 #7）：
 * 1. TOCTOU：admin() 的 installed() 检查与 complete() 落锁之间无互斥，
 *    部署窗口期并发抢注可创建第二个 type=1 超管 → 互斥锁串行化
 * 2. 完成页信息泄露：/install/finish 含管理员邮箱/数据库名，
 *    安装完成后永久对匿名访客可访问 → 仅安装后短窗口内可见
 *
 * 隔离：同 InstallWizardTest（临时 lock 路径 / EnvWriter 实例覆盖 / tearDown 重建库）
 */
class InstallSecurityTest extends TestCase
{
    protected string $tmpEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $lock = config('monit.install_lock');
        if (file_exists($lock)) {
            @unlink($lock);
        }

        @unlink(storage_path('framework/install-mutex.lock'));

        // .env 写入重定向到临时文件，避免污染开发 .env
        $this->tmpEnv = storage_path('framework/testing/install-security.env');
        @mkdir(dirname($this->tmpEnv), 0777, true);
        @unlink($this->tmpEnv);
        $this->app->instance(EnvWriter::class, new EnvWriter($this->tmpEnv));
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpEnv);

        // 恢复干净库结构（本类不用 RefreshDatabase，向导流程动态覆盖 DB 连接）
        try {
            if (Schema::hasTable('migrations')) {
                $this->artisan('migrate:fresh', ['--force' => true]);
            } else {
                $this->artisan('migrate', ['--force' => true]);
            }
        } catch (\Throwable) {
            // 库不可达时跳过
        }

        parent::tearDown();
    }

    protected function requiresCompleteEnvironment(): void
    {
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'curl', 'gd', 'fileinfo', 'tokenizer', 'ctype', 'xml', 'dom'] as $ext) {
            if (! extension_loaded($ext)) {
                $this->markTestSkipped("测试环境缺少 {$ext} 扩展，跳过向导流程用例");
            }
        }
    }

    protected function freshDatabase(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true]);
    }

    protected function makeAdminUser(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'type' => 1,
            'status' => 1,
            'plan_id' => 'free',
        ]);
    }

    /* ---------------- 缺口 1：安装并发竞态 ---------------- */

    public function test_admin_submission_rejected_while_install_in_progress(): void
    {
        $this->requiresCompleteEnvironment();
        $this->freshDatabase();

        // 模拟另一请求正在执行第 4 步（持有互斥锁未释放）
        $mutex = fopen(storage_path('framework/install-mutex.lock'), 'c');
        flock($mutex, LOCK_EX);

        try {
            $response = $this->post('/install/admin', [
                'site_name' => '测试统计平台',
                'site_url' => 'https://stats.example.com',
                'name' => '攻击者',
                'email' => 'attacker@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ]);

            // 持锁期间：直接拒绝（错误回渲染），不得创建管理员、不得落锁
            $response->assertOk();
            $this->assertStringContainsString('安装正在进行中', $response->getContent());
            $this->assertFalse(User::where('type', 1)->exists(), '持锁期间不得创建管理员');
            $this->assertFileDoesNotExist(InstallState::lockPath(), '持锁期间不得写入安装锁');
        } finally {
            flock($mutex, LOCK_UN);
            fclose($mutex);
        }
    }

    /* ---------------- 缺口 2：完成页信息泄露 ---------------- */

    public function test_finish_page_expires_after_grace_window(): void
    {
        $this->freshDatabase();
        $this->makeAdminUser();

        // 安装完成已久（1 小时前）：完成页含管理员邮箱/数据库名，不得继续对匿名访客暴露
        file_put_contents(InstallState::lockPath(), now()->subHour()->toDateTimeString());

        $this->get('/install/finish')->assertRedirect('/');
    }

    public function test_finish_page_accessible_right_after_install(): void
    {
        $this->freshDatabase();
        $this->makeAdminUser();

        // 安装刚完成：站长跳转完成页正常查看汇总（回归保护）
        file_put_contents(InstallState::lockPath(), now()->toDateTimeString());

        $this->get('/install/finish')->assertOk()->assertSee('安装完成');
    }
}
