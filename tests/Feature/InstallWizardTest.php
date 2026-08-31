<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Support\EnvWriter;
use App\Support\InstallState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 网页安装向导端到端（规格书 §15.3 安装器 / §19 部署）
 * 覆盖：EnsureInstalled 拦截 → 三步向导（环境检查 / 数据库+APP_KEY+迁移 / 管理员+核心数据）→ 安装锁
 *
 * 隔离：安装锁与 .env 写入均指向临时路径（phpunit.xml MONIT_INSTALL_LOCK / EnvWriter 实例覆盖）
 */
class InstallWizardTest extends TestCase
{
    protected string $tmpEnv;

    protected function setUp(): void
    {
        parent::setUp();

        // 覆盖基类默认状态：模拟「全新实例」
        $lock = config('monit.install_lock');
        if (file_exists($lock)) {
            @unlink($lock);
        }

        // .env 写入重定向到临时文件，避免污染开发 .env
        $this->tmpEnv = storage_path('framework/testing/install.env');
        @mkdir(dirname($this->tmpEnv), 0777, true);
        @unlink($this->tmpEnv);
        $this->app->instance(EnvWriter::class, new EnvWriter($this->tmpEnv));
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpEnv);
        parent::tearDown();
    }

    public function test_uninstalled_visitors_are_redirected_to_wizard(): void
    {
        // RefreshDatabase 未跑（本类无该 trait）→ :memory: 库可能含旧表；直接删管理员兜底
        $this->assertSame(false, InstallState::installed(), '前置：未安装状态');

        $this->get('/')->assertRedirect('/install');
        $this->get('/login')->assertRedirect('/install');
    }

    public function test_wizard_requirements_page_renders(): void
    {
        $this->get('/install')->assertOk()->assertSee('环境检查');
    }

    public function test_database_step_writes_env_and_migrates(): void
    {
        // 模拟 config 缓存场景：APP_KEY 为空也应被向导补齐
        config(['app.key' => null]);

        $this->post('/install/database', ['connection' => 'sqlite'])
            ->assertRedirect(route('install.admin'));

        $env = (string) file_get_contents($this->tmpEnv);
        $this->assertStringContainsString('DB_CONNECTION=sqlite', $env);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:/m', $env);
        $this->assertNotEmpty(config('app.key'), 'APP_KEY 已同步到当前进程');

        // 生产加固：.env.example 的 local/debug=true 不能带上生产
        $this->assertStringContainsString('APP_ENV=production', $env);
        $this->assertStringContainsString('APP_DEBUG=false', $env);

        // 迁移已执行
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_admin_step_creates_admin_seeds_core_data_and_locks(): void
    {
        $this->artisan('migrate', ['--force' => true]);
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\CoreDataSeeder', '--force' => true]);
        // 清掉 seed 写入的状态，回到「未安装但库已就绪」
        User::query()->delete();

        $this->post('/install/admin', [
            'name' => '站长',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('login'));

        $admin = User::where('type', 1)->first();
        $this->assertNotNull($admin, '管理员已创建');
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertSame('pro', $admin->plan_id);
        $this->assertNotNull($admin->email_verified_at, '邮箱已标记验证（forceFill 绕过 fillable）');
        $this->assertNotEmpty($admin->api_key, 'API Token 已生成（开箱即用）');
        $this->assertNotEmpty($admin->referral_key, '推荐码已生成');

        // 不创建任何演示账号
        $this->assertSame(1, User::count());
        $this->assertNull(User::where('email', 'admin@monit.dev')->first());

        // 核心数据（套餐/设置）由向导入库
        $this->assertGreaterThanOrEqual(2, Plan::count());

        // 安装锁写入：向导失效、业务路由放行
        $this->assertFileExists(config('monit.install_lock'));
        $this->get('/install')->assertRedirect('/');
        $this->get('/')->assertOk();
    }

    public function test_installed_admin_can_login_and_reach_dashboard(): void
    {
        $this->artisan('migrate', ['--force' => true]);

        $this->post('/install/admin', [
            'name' => '站长',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('login'));

        // 「装完能用」核心断言：真实走 Session（database 驱动）+ Cookie 加密登录链路
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_installed_instance_redirects_wizard_to_home(): void
    {
        InstallState::complete();

        $this->get('/install')->assertRedirect('/');
    }

    public function test_mysql_missing_fields_show_chinese_validation_errors(): void
    {
        $this->post('/install/database', ['connection' => 'mysql'])
            ->assertOk()
            ->assertSee('选择 MySQL 时必须填写主机地址')
            ->assertSee('选择 MySQL 时必须填写数据库名')
            ->assertSee('选择 MySQL 时必须填写用户名');
    }

    public function test_mysql_illegal_database_name_is_rejected(): void
    {
        // 库名会拼入 CREATE DATABASE：反引号/引号等必须被验证拦截
        $this->post('/install/database', [
            'connection' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'monit`; DROP DATABASE x',
            'username' => 'root',
        ])->assertOk()->assertSee('数据库名只能包含字母、数字、下划线和中划线');
    }

    public function test_mysql_unreachable_host_shows_friendly_error(): void
    {
        // 指向未监听端口：PDO 立即 Connection refused，翻译为中文指引而非英文堆栈
        $this->post('/install/database', [
            'connection' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 33990,
            'database' => 'monit_test',
            'username' => 'root',
            'password' => 'x',
        ])->assertOk()->assertSee('无法连接 MySQL 服务器');
    }

    public function test_sqlite_install_clears_leftover_mysql_env_keys(): void
    {
        // 服务器常见现场：旧 .env 残留 MySQL 配置；sqlite 安装必须清掉
        //（DB_DATABASE 残留会把 sqlite 路径劫持为旧库名，装完即 500）
        $env = $this->app->make(EnvWriter::class);
        $env->write('DB_CONNECTION', 'mysql');
        $env->write('DB_HOST', '127.0.0.1');
        $env->write('DB_DATABASE', 'old_monit');
        $env->write('DB_USERNAME', 'root');
        $env->write('DB_PASSWORD', 'secret');

        $this->post('/install/database', ['connection' => 'sqlite'])
            ->assertRedirect(route('install.admin'));

        $content = (string) file_get_contents($this->tmpEnv);
        $this->assertStringContainsString('DB_CONNECTION=sqlite', $content);
        foreach (['DB_HOST=', 'DB_PORT=', 'DB_DATABASE=', 'DB_USERNAME=', 'DB_PASSWORD='] as $stale) {
            $this->assertStringNotContainsString($stale, $content, "残留键 {$stale} 应被清除");
        }
        $this->assertTrue(Schema::hasTable('users'), '迁移在干净的 sqlite 上完成');
    }

    /**
     * 真实 MySQL 安装 E2E（可选）：提供 MONIT_TEST_MYSQL_HOST 等环境变量即执行
     * 例：MONIT_TEST_MYSQL_HOST=127.0.0.1 MONIT_TEST_MYSQL_USERNAME=root MONIT_TEST_MYSQL_PASSWORD=xxx vendor/bin/phpunit --filter mysql_real
     */
    public function test_mysql_real_install_if_env_provided(): void
    {
        $host = env('MONIT_TEST_MYSQL_HOST');

        if (! $host || ! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('未设置 MONIT_TEST_MYSQL_* 或缺少 pdo_mysql，跳过真实 MySQL 安装测试');
        }

        $this->post('/install/database', [
            'connection' => 'mysql',
            'host' => $host,
            'port' => (int) env('MONIT_TEST_MYSQL_PORT', 3306),
            'database' => (string) env('MONIT_TEST_MYSQL_DATABASE', 'monit_install_test'),
            'username' => (string) env('MONIT_TEST_MYSQL_USERNAME', 'root'),
            'password' => (string) env('MONIT_TEST_MYSQL_PASSWORD', ''),
        ])->assertRedirect(route('install.admin'));

        $content = (string) file_get_contents($this->tmpEnv);
        $this->assertStringContainsString('DB_CONNECTION=mysql', $content);
        $this->assertStringContainsString('DB_DATABASE=monit', $content);

        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertTrue(Schema::hasTable('users'), '迁移已在 MySQL 上完成（库不存在时向导应已自动建库）');
    }
}
