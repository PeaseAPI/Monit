<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Support\EnvWriter;
use App\Support\InstallState;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 网页安装向导端到端（规格书 §15.3 安装器 / §19 部署）
 * 覆盖：EnsureInstalled 拦截 → 五步向导（环境检测 / 目录权限 / 数据库+APP_KEY+自动建库+迁移 / 站点与管理员+核心数据 / 完成页）→ 安装锁
 *
 * 隔离：安装锁与 .env 写入均指向临时路径（phpunit.xml MONIT_INSTALL_LOCK / EnvWriter 实例覆盖）；
 * 数据库用独立测试库 monit_test（phpunit.xml DB_*），各用例按需 migrate:fresh
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

        // 恢复干净库结构：本类不使用 RefreshDatabase（向导会动态改写 DB 连接），
        // 真实提交的数据会泄漏到后续测试类——而 RefreshDatabase 的 static::$migrated
        // 已置真，后续类不会再 migrate:fresh，故在此重建空结构兜底
        try {
            if (Schema::hasTable('migrations')) {
                $this->artisan('migrate:fresh', ['--force' => true]);
            } else {
                $this->artisan('migrate', ['--force' => true]);
            }
        } catch (\Throwable) {
            // 库不可达时跳过（如仅跑纯单元用例）
        }

        parent::tearDown();
    }

    /**
     * 测试环境必需扩展齐备才执行流程类用例（CI 缺 gd 等时跳过而非误报）
     */
    protected function requiresCompleteEnvironment(): void
    {
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'curl', 'gd', 'fileinfo', 'tokenizer', 'ctype', 'xml', 'dom'] as $ext) {
            if (! extension_loaded($ext)) {
                $this->markTestSkipped("测试环境缺少 {$ext} 扩展，跳过向导流程用例");
            }
        }
    }

    /**
     * 清空测试库（无 RefreshDatabase：向导步骤会动态覆盖 DB 连接配置，需手动重置）
     */
    protected function freshDatabase(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true]);
    }

    /**
     * 连表一起清（构造「未迁移」状态：InstallState 数据库兜底要求 users 表/管理员不存在）
     */
    protected function dropAllTables(): void
    {
        Schema::dropAllTables();
    }

    /* ------------------------------------------------------------------ */
    /* 守卫与步骤渲染                                                       */
    /* ------------------------------------------------------------------ */

    public function test_uninstalled_visitors_are_redirected_to_wizard(): void
    {
        // InstallState 兜底「users 有管理员=已安装」：清库确保未安装判定不被上一用例污染
        $this->dropAllTables();
        $this->assertSame(false, InstallState::installed(), '前置：未安装状态');

        $this->get('/')->assertRedirect('/install');
        $this->get('/login')->assertRedirect('/install');
    }

    public function test_wizard_requirements_page_renders(): void
    {
        $this->get('/install')->assertOk()->assertSee('环境检测');
    }

    public function test_requirements_step_redirects_to_permissions(): void
    {
        $this->requiresCompleteEnvironment();

        $this->post('/install/requirements')->assertRedirect(route('install.permissions'));
    }

    public function test_permissions_step_renders_and_redirects_to_database(): void
    {
        $this->requiresCompleteEnvironment();

        $this->get('/install/permissions')->assertOk()->assertSee('目录权限');
        $this->post('/install/permissions')->assertRedirect(route('install.database'));
    }

    public function test_admin_step_guards_against_unmigrated_database(): void
    {
        // 未执行迁移（users 表不存在）时第 4 步回跳第 3 步
        $this->dropAllTables();
        $this->get('/install/admin')->assertRedirect(route('install.database'));
    }

    /* ------------------------------------------------------------------ */
    /* 第 3 步：数据库（MySQL 唯一）                                        */
    /* ------------------------------------------------------------------ */

    public function test_database_step_rejects_missing_fields(): void
    {
        $this->post('/install/database', [])
            ->assertOk()
            ->assertViewHas('step', 'database');
    }

    public function test_database_step_rejects_illegal_database_name(): void
    {
        // 库名会拼入 CREATE DATABASE：反引号/引号等必须被验证拦截
        $this->post('/install/database', [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'monit`; DROP DATABASE x',
            'username' => 'root',
        ])->assertOk()->assertViewHas('step', 'database');
    }

    public function test_database_step_unreachable_host_shows_friendly_error(): void
    {
        // 指向未监听端口：PDO 立即 Connection refused，翻译为中文指引而非英文堆栈
        $this->post('/install/database', [
            'host' => '127.0.0.1',
            'port' => 33990,
            'database' => 'monit_test',
            'username' => 'root',
            'password' => 'x',
        ])->assertOk()->assertSee('无法连接 MySQL 服务器');
    }

    public function test_testdb_endpoint_returns_json_diagnostics(): void
    {
        // 错误凭据 → ok:false + 中文翻译
        $this->postJson('/install/test-db', [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'monit_test',
            'username' => 'root',
            'password' => 'definitely-wrong',
        ])->assertOk()->assertJsonPath('ok', false)
          ->assertSee('Access denied');

        // 正确凭据（phpunit.xml 提供的测试库连接）→ ok:true + 版本号
        $cfg = config('database.connections.mysql');
        $this->postJson('/install/test-db', [
            'host' => $cfg['host'],
            'port' => (int) $cfg['port'],
            'database' => $cfg['database'],
            'username' => $cfg['username'],
            'password' => (string) $cfg['password'],
        ])->assertOk()->assertJsonPath('ok', true)
          ->assertJsonStructure(['message', 'version']);
    }

    public function test_database_step_writes_env_and_migrates(): void
    {
        $this->requiresCompleteEnvironment();
        $this->freshDatabase();

        // 模拟 config 缓存场景：APP_KEY 为空也应被向导补齐
        config(['app.key' => null]);

        $cfg = config('database.connections.mysql');
        $this->post('/install/database', [
            'host' => $cfg['host'],
            'port' => (int) $cfg['port'],
            'database' => $cfg['database'],
            'username' => $cfg['username'],
            'password' => (string) $cfg['password'],
        ])->assertRedirect(route('install.admin'));

        $env = (string) file_get_contents($this->tmpEnv);
        $this->assertStringContainsString('DB_CONNECTION=mysql', $env);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:/m', $env);
        $this->assertNotEmpty(config('app.key'), 'APP_KEY 已同步到当前进程');

        // 生产加固：.env.example 的 local/debug=true 不能带上生产
        $this->assertStringContainsString('APP_ENV=production', $env);
        $this->assertStringContainsString('APP_DEBUG=false', $env);

        // 迁移已执行
        $this->assertTrue(Schema::hasTable('users'));
    }

    /* ------------------------------------------------------------------ */
    /* 第 4/5 步：站点与管理员 → 完成页                                     */
    /* ------------------------------------------------------------------ */

    public function test_admin_step_creates_admin_seeds_core_data_and_locks(): void
    {
        $this->freshDatabase();

        $this->post('/install/admin', [
            'site_name' => '测试统计平台',
            'site_url' => 'https://stats.example.com',
            'name' => '站长',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('install.finish'));

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

        // 核心数据（套餐/站点设置）由向导入库并覆盖
        $this->assertGreaterThanOrEqual(2, Plan::count());
        $this->assertSame('测试统计平台', \App\Support\Settings::get('site_name'));
        $this->assertSame('https://stats.example.com', \App\Support\Settings::get('site_url'));

        // APP_URL 以用户填写为准写入 .env
        $this->assertStringContainsString(
            'APP_URL=https://stats.example.com',
            (string) file_get_contents($this->tmpEnv)
        );

        // 安装锁写入：向导失效、业务路由放行
        $this->assertFileExists(config('monit.install_lock'));
        $this->get('/install')->assertRedirect('/');
        $this->get('/')->assertOk();
    }

    public function test_admin_step_validates_site_and_password(): void
    {
        $this->freshDatabase();

        // 缺 site_name、密码不一致 → 回渲染第 4 步并显示中文错误
        $this->post('/install/admin', [
            'site_url' => 'https://stats.example.com',
            'name' => '站长',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'different-password',
        ])->assertOk()
          ->assertViewHas('step', 'admin')
          ->assertSee('请填写网站名称')
          ->assertSee('两次输入的密码不一致');
    }

    public function test_finish_page_renders_summary_after_install(): void
    {
        $this->freshDatabase();

        $this->post('/install/admin', [
            'site_name' => '完成页测试',
            'site_url' => 'https://finish.example.com',
            'name' => 'Admin',
            'email' => 'finish@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('install.finish'));

        $this->get('/install/finish')
            ->assertOk()
            ->assertSee('安装完成')
            ->assertSee('finish@example.com')
            ->assertSee('MySQL · '.config('database.connections.mysql.database'));
    }

    public function test_finish_page_redirects_to_wizard_when_not_installed(): void
    {
        // 清库排除兜底判定（否则上一用例残留的管理员使 installed()=true）
        $this->dropAllTables();
        $this->get('/install/finish')->assertRedirect(route('install'));
    }

    public function test_installed_admin_can_login_and_reach_dashboard(): void
    {
        $this->freshDatabase();

        $this->post('/install/admin', [
            'site_name' => '登录测试',
            'site_url' => 'http://localhost',
            'name' => '站长',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('install.finish'));

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

        // /install 及各步骤失效跳首页；完成页例外（收尾汇总仍可访问）
        $this->get('/install')->assertRedirect('/');
        $this->get('/install/database')->assertRedirect('/');
        $this->get('/install/admin')->assertRedirect('/');
    }

    public function test_installed_instance_still_allows_finish_page(): void
    {
        $this->freshDatabase();

        $this->post('/install/admin', [
            'site_name' => '锁定后完成页',
            'site_url' => 'http://localhost',
            'name' => 'Admin',
            'email' => 'locked@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('install.finish'));

        $this->get('/install/finish')->assertOk()->assertSee('locked@example.com');
    }
}
