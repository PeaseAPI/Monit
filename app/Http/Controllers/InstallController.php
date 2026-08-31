<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EnvWriter;
use App\Support\InstallState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

/**
 * Web 安装向导（规格书 §15.3：安装器本地化，§19 部署）
 * 三步：环境检查 → 数据库（写 .env + 生成 APP_KEY + 迁移） → 管理员账户（+ 核心初始数据）
 * 安装完成后写入 installed.lock，向导即失效（EnsureInstalled 中间件兜底跳首页）。
 *
 * 本控制器挂在无中间件路由组（routes/install.php）：
 * - 无 Session/CSRF：校验错误直接回传视图渲染，不使用 redirect()->withErrors()/old()
 * - .env 写入复用 App\Support\EnvWriter（键白名单 + 值转义 + 原子写，防注入）
 */
class InstallController extends Controller
{
    public function __construct(protected EnvWriter $env) {}

    public function index(): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        $envPath = $this->env->path();
        if (! file_exists($envPath) && file_exists(base_path('.env.example'))) {
            copy(base_path('.env.example'), $envPath);
        }

        $checks = $this->checks();

        return view('install', [
            'step' => 'requirements',
            'checks' => $checks,
            'allPassed' => ! in_array(false, $checks, true),
            'errors' => [],
        ]);
    }

    /**
     * 步骤 2：数据库配置 + APP_KEY + 迁移
     */
    public function database(Request $request): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        $validated = Validator::make($request->all(), [
            'connection' => ['required', 'in:sqlite,mysql'],
            'host' => ['required_if:connection,mysql', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:connection,mysql', 'nullable', 'integer', 'between:1,65535'],
            // 库名限制字符集：库名会拼入 CREATE DATABASE（反引号包裹 + 此处白名单双保险）
            'database' => ['required_if:connection,mysql', 'nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'username' => ['required_if:connection,mysql', 'nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ], [
            'connection.required' => '请选择数据库类型',
            'connection.in' => '数据库类型仅支持 sqlite 或 mysql',
            'host.required_if' => '选择 MySQL 时必须填写主机地址',
            'port.required_if' => '选择 MySQL 时必须填写端口',
            'port.between' => 'MySQL 端口必须在 1-65535 之间',
            'database.required_if' => '选择 MySQL 时必须填写数据库名',
            'database.regex' => '数据库名只能包含字母、数字、下划线和中划线',
            'username.required_if' => '选择 MySQL 时必须填写用户名',
        ]);

        if ($validated->fails()) {
            return $this->backToRequirements($validated->errors()->all());
        }

        $data = $validated->validated();

        try {
            $this->applyDatabaseConfig($data);
            $this->ensureAppKey();
            $this->hardenEnv();

            // 迁移（--force：生产环境免确认）
            // Artisan::call 失败默认不抛异常（返回非 0 退出码），必须显式检查——
            // 否则迁移失败也会跳到第 3 步，管理员创建时才炸出难懂错误
            $exit = Artisan::call('migrate', ['--force' => true]);
            if ($exit !== 0) {
                throw new RuntimeException('数据库迁移失败：'.trim(Artisan::output()));
            }
        } catch (\Throwable $e) {
            return $this->backToRequirements([$e->getMessage()]);
        }

        return redirect()->route('install.admin');
    }

    /**
     * 步骤 3：创建管理员
     */
    public function showAdmin(Request $request): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        if (! Schema::hasTable('users')) {
            return redirect()->route('install');
        }

        return view('install', ['step' => 'admin', 'old' => [], 'errors' => []]);
    }

    public function admin(Request $request): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        $validated = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validated->fails()) {
            return view('install', [
                'step' => 'admin',
                'old' => $request->only(['name', 'email']),
                'errors' => $validated->errors()->all(),
            ]);
        }

        if (! Schema::hasTable('users')) {
            return redirect()->route('install');
        }

        $data = $validated->validated();

        // 核心初始数据先就位：free/pro 套餐 + 平台默认设置（不含任何演示账号）
        $exit = Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CoreDataSeeder', '--force' => true]);
        if ($exit !== 0) {
            return $this->backToAdmin($request, ['初始数据写入失败：'.trim(Artisan::output())]);
        }

        try {
            $admin = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // User::$casts password=hashed 自动加密
                'type' => 1,
                'status' => 1,
                'plan_id' => 'pro',
                // 开箱即用：API Token 与推荐返佣码（列为 nullable，但对应功能依赖它们）
                'api_key' => Str::random(60),
                'referral_key' => Str::random(32),
                'language' => 'zh_CN',
                'timezone' => 'Asia/Shanghai',
            ]);

            // email_verified_at 不在 $fillable（防注册端自填已验证标记），此处显式写入
            $admin->forceFill(['email_verified_at' => now()])->save();
        } catch (\Throwable $e) {
            return $this->backToAdmin($request, ['管理员写入失败：'.$e->getMessage()]);
        }

        // 写入安装锁：此后向导失效
        InstallState::complete();

        // 若存在旧 config 缓存（空 APP_KEY / 旧 DB_* 固化），清除使其重新读取 .env
        Artisan::call('config:clear');

        return redirect()->route('login');
    }

    /**
     * 环境检查项
     */
    protected function checks(): array
    {
        // 键即页面显示标签（install.blade.php 直接渲染键名）
        return [
            'PHP ≥ 8.2（当前 '.PHP_VERSION.'）' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'PDO 扩展' => extension_loaded('pdo'),
            'PDO SQLite 扩展' => extension_loaded('pdo_sqlite'),
            'Mbstring 扩展' => extension_loaded('mbstring'),
            'GD 扩展' => extension_loaded('gd'),
            'storage/ 目录可写' => is_writable(storage_path()),
            'database/ 目录可写' => is_writable(database_path()),
            '项目根目录可写（生成 .env）' => is_writable(base_path()),
            '环境配置文件可创建' => file_exists($this->env->path()) || file_exists(base_path('.env.example')),
        ];
    }

    /**
     * 数据库配置：持久化 .env（下次启动生效）+ 当前进程立即生效 + 连接预检
     *
     * 关键：config 缓存环境下 Artisan::call 不会重读 .env，
     * 必须 config() + DB::purge() 动态切换连接，否则 migrate 连的还是旧库。
     */
    protected function applyDatabaseConfig(array $data): void
    {
        $connection = $data['connection'];

        // 1) 持久化到 .env（EnvWriter：键白名单 + 值转义 + 原子写）
        $this->env->write('DB_CONNECTION', $connection);

        if ($connection === 'mysql') {
            foreach (['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username'] as $envKey => $field) {
                $this->env->write($envKey, (string) ($data[$field] ?? ''));
            }
            $this->env->write('DB_PASSWORD', (string) ($data['password'] ?? ''));

            // 当前进程立即生效（覆盖缓存的旧连接参数）
            config(['database.default' => 'mysql']);
            config(['database.connections.mysql' => array_merge(config('database.connections.mysql') ?? [], [
                'host' => $data['host'],
                'port' => (int) $data['port'],
                'database' => $data['database'],
                'username' => $data['username'],
                'password' => $data['password'] ?? '',
            ])]);

            if (! extension_loaded('pdo_mysql')) {
                throw new RuntimeException('PHP 缺少 pdo_mysql 扩展：请在 PHP 中安装/启用 pdo_mysql（宝塔：软件商店 → PHP → 安装扩展），或改用 SQLite');
            }

            DB::purge();
            $this->ensureMysqlDatabase();
        } else {
            // sqlite：清掉旧 .env 可能残留的 MySQL 配置——
            // DB_DATABASE 残留会把 sqlite 路径劫持为旧库名（config/database.php 读该 env），装完即 500
            foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $staleKey) {
                $this->env->write($staleKey, null); // EnvWriter 空值 = 删除该键
            }

            $sqlite = database_path('database.sqlite');

            if (file_exists($sqlite) && ! is_writable($sqlite)) {
                // 高频生产事故：曾用 root 执行 migrate/seed → 库文件属主 root，PHP-FPM 只读
                //（attempt to write a readonly database）。删除只需父目录写权限，这里自动重建新库
                if (! @unlink($sqlite)) {
                    throw new RuntimeException(
                        '数据库文件不可写且无法自动重建：'.$sqlite.'。'.
                        '请在服务器执行 chown -R www:www database storage bootstrap/cache 后刷新本页重试'.
                        '（宝塔/常见面板 PHP 用户为 www，其他环境请替换为你的 PHP-FPM 运行用户）'
                    );
                }
            }

            if (! file_exists($sqlite)) {
                file_put_contents($sqlite, '');
            }
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $sqlite,
            ]);
            DB::purge();
        }

        // 2) 连接预检：凭据错误 / 库不存在 / 目录不可写在迁移前直接暴露为可读错误
        try {
            DB::connection($connection)->getPdo();
        } catch (\Throwable $e) {
            throw new RuntimeException($this->translateDbError($e->getMessage()));
        }
    }

    /**
     * MySQL 就绪检查：以 server 级连接（不指定库）验证凭据可达，
     * 目标库不存在时自动创建（utf8mb4）——无建库权限则给出明确指引
     */
    protected function ensureMysqlDatabase(): void
    {
        $cfg = config('database.connections.mysql');
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (string) ($cfg['port'] ?? '3306');
        $database = (string) ($cfg['database'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');

        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $username,
                $password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_TIMEOUT => 5,
                ]
            );
        } catch (\PDOException $e) {
            throw new RuntimeException($this->translateDbError($e->getMessage()));
        }

        // 库名已由验证规则限制为 [A-Za-z0-9_-]，反引号包裹 + 剔除双保险
        $safeName = str_replace('`', '', $database);

        try {
            $pdo->exec(
                "CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );
        } catch (\PDOException $e) {
            throw new RuntimeException(
                'MySQL 账户无权创建数据库「'.$database.'」：请先在 MySQL 中手动创建该库'.
                '（字符集 utf8mb4）并授权本账户读写，然后返回重试。原始错误：'.$e->getMessage()
            );
        }
    }

    /**
     * 常见连接/驱动错误翻译为中文指引（未匹配的原文透传，便于排查）
     */
    protected function translateDbError(string $raw): string
    {
        return match (true) {
            str_contains($raw, 'Access denied for user')
                => 'MySQL 用户名或密码错误（Access denied）。请核对后重试。原始错误：'.$raw,
            str_contains($raw, '[2002]')
                || str_contains($raw, 'Connection refused')
                || str_contains($raw, 'server has gone away')
                => '无法连接 MySQL 服务器：请检查主机地址与端口是否正确、MySQL 是否在运行、防火墙/安全组是否放行。原始错误：'.$raw,
            str_contains($raw, 'Unknown database')
                || str_contains($raw, '[1049]')
                => '数据库不存在且无法自动创建。请先手动建库后重试。原始错误：'.$raw,
            str_contains($raw, 'could not find driver')
                => 'PHP 缺少对应数据库驱动扩展（pdo_mysql / pdo_sqlite）。原始错误：'.$raw,
            default => '数据库连接失败：'.$raw,
        };
    }

    /**
     * 无 Session 环境：错误直接回渲染第 3 步页面
     *
     * @param  list<string>  $errors
     */
    protected function backToAdmin(Request $request, array $errors): View
    {
        return view('install', [
            'step' => 'admin',
            'old' => $request->only(['name', 'email']),
            'errors' => $errors,
        ]);
    }

    /**
     * 确保 APP_KEY：.env 已有则同步到当前进程 config；缺失则生成并双写（.env + config）
     */
    protected function ensureAppKey(): void
    {
        if (! empty(config('app.key'))) {
            return;
        }

        $existing = $this->env->read('APP_KEY');

        $key = ! empty($existing)
            ? $existing
            : 'base64:'.base64_encode(random_bytes(32));

        $this->env->write('APP_KEY', $key);

        // 当前进程立即生效：安装完成跳转登录页后 Cookie 加密即刻可用
        config(['app.key' => $key]);
    }

    /**
     * 生产加固：.env.example 默认 local / debug=true / localhost，
     * 原样带到线上会暴露堆栈、生成错误链接——安装时一次性修正
     */
    protected function hardenEnv(): void
    {
        $this->env->write('APP_ENV', 'production');
        $this->env->write('APP_DEBUG', 'false');

        // APP_URL 取当前访问地址（密码重置/邮件/静态资源链接依赖）；本机访问时保留原值
        $host = (string) request()->getHost();
        if ($host !== '' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $url = request()->getScheme().'://'.$host;
            $this->env->write('APP_URL', $url);
            config(['app.url' => $url]);
        }

        // 当前进程立即生效
        config(['app.env' => 'production', 'app.debug' => false]);
    }

    /**
     * 无 Session 环境：错误直接回渲染第 1 步页面（不走 back()->withErrors）
     *
     * @param  list<string>  $errors
     */
    protected function backToRequirements(array $errors): View
    {
        return view('install', [
            'step' => 'requirements',
            'checks' => $this->checks(),
            'allPassed' => false,
            'errors' => $errors,
        ]);
    }
}
