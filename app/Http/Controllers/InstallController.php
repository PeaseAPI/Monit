<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EnvWriter;
use App\Support\InstallState;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

/**
 * Web 安装向导（规格书 §15.3：安装器本地化，§19 部署）
 *
 * 五步流程（MySQL 唯一支持，SQLite 已移除）：
 *   1. 环境检测（PHP 版本 / 必需扩展 / 推荐项 / 磁盘空间）
 *   2. 目录权限检测（storage / bootstrap/cache / .env 可写）
 *   3. 数据库配置（MySQL 连接测试 → 写 .env + 生成 APP_KEY + 自动建库 + 迁移）
 *   4. 站点与管理员（网站名称 / URL + 超级管理员账户 + 核心初始数据）
 *   5. 完成页（汇总信息 + 登录入口 + Cron 部署指引）
 *
 * 步骤推进无需 Session（无中间件路由组）：每一步的前置条件实时推导——
 *   - 步骤 2/3：环境/权限检测为纯计算，进入时实时校验
 *   - 步骤 4：users 表已存在（步骤 3 迁移完成）
 *   - 步骤 5：InstallState::installed()（管理员已建 + installed.lock）
 *
 * 本控制器挂在无中间件路由组（routes/install.php）：
 * - 无 Session/CSRF：校验错误直接回传视图渲染，不使用 redirect()->withErrors()/old()
 * - .env 写入复用 App\Support\EnvWriter（键白名单 + 值转义 + 原子写，防注入）
 */
class InstallController extends Controller
{
    public function __construct(protected EnvWriter $env) {}

    /* 步骤 1：环境检测 --------------------------------------------------- */

    public function requirements(): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        $this->ensureEnvFile();

        return view('install', [
            'step' => 'requirements',
            'checks' => $this->environmentChecks(),
            'errors' => [],
        ]);
    }

    public function checkRequirements(): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        $checks = $this->environmentChecks();
        $failed = array_filter($checks, fn (array $c) => $c['required'] && ! $c['passed']);

        if ($failed !== []) {
            return view('install', [
                'step' => 'requirements',
                'checks' => $checks,
                'errors' => ['有 '.count($failed).' 项必需环境检测未通过，请按提示修复后重新检测。'],
            ]);
        }

        return redirect()->route('install.permissions');
    }

    /* 步骤 2：目录权限检测 ----------------------------------------------- */

    public function permissions(): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        // 前置：环境检测必须通过
        $failedEnv = array_filter($this->environmentChecks(), fn (array $c) => $c['required'] && ! $c['passed']);
        if ($failedEnv !== []) {
            return redirect()->route('install');
        }

        return view('install', [
            'step' => 'permissions',
            'checks' => $this->permissionChecks(),
            'errors' => [],
        ]);
    }

    public function checkPermissions(): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        $checks = $this->permissionChecks();
        $failed = array_filter($checks, fn (array $c) => ! $c['passed']);

        if ($failed !== []) {
            return view('install', [
                'step' => 'permissions',
                'checks' => $checks,
                'errors' => ['有 '.count($failed).' 个目录/文件权限检测未通过，请按提示修复后点击「重新检测」。'],
            ]);
        }

        return redirect()->route('install.database');
    }

    /**
     * 环境检测清单
     *
     * @return list<array{label: string, current: string, required: bool, passed: bool, hint: string}>
     */
    protected function environmentChecks(): array
    {
        $diskFree = function_exists('disk_free_space') && disk_free_space(base_path()) !== false
            ? (int) round(((float) disk_free_space(base_path())) / 1048576)
            : 0;

        return [
            [
                'label' => 'PHP 版本 ≥ 8.2',
                'current' => PHP_VERSION,
                'required' => true,
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'hint' => '当前 PHP 版本过低，请升级到 8.2 或以上（宝塔：软件商店 → 切换 PHP 版本）',
            ],
            [
                'label' => 'PDO MySQL 扩展（pdo_mysql）',
                'current' => extension_loaded('pdo_mysql') ? '已启用' : '未启用',
                'required' => true,
                'passed' => extension_loaded('pdo_mysql'),
                'hint' => 'Monit 仅支持 MySQL：请安装/启用 pdo_mysql 扩展（宝塔：PHP → 安装扩展 → pdo_mysql）',
            ],
            [
                'label' => 'Mbstring 扩展',
                'current' => extension_loaded('mbstring') ? '已启用' : '未启用',
                'required' => true,
                'passed' => extension_loaded('mbstring'),
                'hint' => '多字节字符串处理（中文必需）：请启用 mbstring 扩展',
            ],
            [
                'label' => 'OpenSSL 扩展',
                'current' => extension_loaded('openssl') ? '已启用' : '未启用',
                'required' => true,
                'passed' => extension_loaded('openssl'),
                'hint' => '加密与 HTTPS 通信必需：请启用 openssl 扩展',
            ],
            [
                'label' => 'cURL 扩展',
                'current' => extension_loaded('curl') ? '已启用' : '未启用',
                'required' => true,
                'passed' => extension_loaded('curl'),
                'hint' => '支付回调 / 社交登录 / SEO 审计等外联请求必需：请启用 curl 扩展',
            ],
            [
                'label' => 'GD 扩展（图像处理）',
                'current' => extension_loaded('gd') ? '已启用' : '未启用',
                'required' => true,
                'passed' => extension_loaded('gd'),
                'hint' => '验证码 / 动态 OG 图 / 站点图标生成必需：请启用 gd 扩展',
            ],
            [
                'label' => 'fileinfo 扩展',
                'current' => extension_loaded('fileinfo') ? '已启用' : '未启用',
                'required' => true,
                'passed' => extension_loaded('fileinfo'),
                'hint' => '文件上传类型识别必需：请启用 fileinfo 扩展',
            ],
            [
                'label' => 'Tokenizer / Ctype / XML / DOM',
                'current' => (extension_loaded('tokenizer') && extension_loaded('ctype') && extension_loaded('xml') && extension_loaded('dom')) ? '已启用' : '未启用',
                'required' => true,
                'passed' => extension_loaded('tokenizer') && extension_loaded('ctype') && extension_loaded('xml') && extension_loaded('dom'),
                'hint' => '框架基础依赖：请启用 tokenizer / ctype / xml / dom 扩展',
            ],
            [
                'label' => '磁盘剩余空间 ≥ 100MB',
                'current' => $diskFree > 0 ? $diskFree.' MB' : '无法检测',
                'required' => true,
                'passed' => $diskFree >= 100,
                'hint' => '日志 / 缓存 / 回放数据需要至少 100MB 可用空间',
            ],
            [
                'label' => 'intl 扩展（推荐）',
                'current' => extension_loaded('intl') ? '已启用' : '未启用',
                'required' => false,
                'passed' => extension_loaded('intl'),
                'hint' => '推荐启用：国际化（语言/地区）支持更完善',
            ],
            [
                'label' => 'allow_url_fopen（推荐）',
                'current' => ((bool) ini_get('allow_url_fopen')) ? '已开启' : '未开启',
                'required' => false,
                'passed' => (bool) ini_get('allow_url_fopen'),
                'hint' => '推荐开启：部分远程资源读取依赖此配置（php.ini）',
            ],
        ];
    }

    /**
     * 目录权限检测清单：检测前先自动补建缺失目录（storage 子结构）
     *
     * @return list<array{label: string, current: string, passed: bool, hint: string}>
     */
    protected function permissionChecks(): array
    {
        // storage 框架子结构缺失时自动创建
        foreach (['framework/cache', 'framework/sessions', 'framework/views', 'logs', 'app'] as $sub) {
            $dir = storage_path($sub);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        $targets = [
            [storage_path(), 'storage/（缓存 · 会话 · 日志）'],
            [storage_path('app'), 'storage/app/（应用文件）'],
            [storage_path('framework'), 'storage/framework/（编译视图）'],
            [storage_path('framework/cache'), 'storage/framework/cache/（缓存）'],
            [storage_path('framework/sessions'), 'storage/framework/sessions/（会话）'],
            [storage_path('framework/views'), 'storage/framework/views/（视图编译）'],
            [storage_path('logs'), 'storage/logs/（日志）'],
            [base_path('bootstrap/cache'), 'bootstrap/cache/（配置缓存）'],
        ];

        $checks = [];
        foreach ($targets as [$path, $label]) {
            $writable = is_dir($path) && is_writable($path);
            $checks[] = [
                'label' => $label.' 可写',
                'current' => $writable ? '可写' : (is_dir($path) ? '不可写' : '不存在'),
                'passed' => $writable,
                'hint' => '执行：chmod -R ug+rwX '.escapeshellarg($path).' 并确保属主为 PHP 运行用户（宝塔为 www）',
            ];
        }

        // .env：已存在则检测可写，不存在则检测根目录可写（安装时生成）
        $envPath = $this->env->path();
        if (file_exists($envPath)) {
            $envOk = is_writable($envPath);
            $envHint = '执行：chmod 660 '.$envPath.'（属主为 PHP 运行用户）';
            $envCurrent = $envOk ? '可写' : '不可写';
        } else {
            $envOk = is_writable(base_path());
            $envHint = '执行：chmod ug+w '.base_path().'（安装时需在根目录生成 .env）';
            $envCurrent = $envOk ? '根目录可写（.env 可生成）' : '根目录不可写';
        }
        $checks[] = [
            'label' => '.env 配置文件可写',
            'current' => $envCurrent,
            'passed' => $envOk,
            'hint' => $envHint,
        ];

        return $checks;
    }

    /* 步骤 3：数据库配置（MySQL） ------------------------------------------ */

    public function showDatabase(): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        // 前置：环境 + 权限均通过
        $failedEnv = array_filter($this->environmentChecks(), fn (array $c) => $c['required'] && ! $c['passed']);
        $failedPerm = array_filter($this->permissionChecks(), fn (array $c) => ! $c['passed']);
        if ($failedEnv !== [] || $failedPerm !== []) {
            return redirect()->route('install');
        }

        return view('install', [
            'step' => 'database',
            'errors' => [],
            'old' => [],
        ]);
    }

    /**
     * AJAX 连接测试：server 级连通 + 凭据校验 + 目标库存在性（不写 .env、不迁移）
     */
    public function testConnection(Request $request): JsonResponse
    {
        if (InstallState::installed()) {
            return response()->json(['ok' => false, 'message' => '系统已安装，安装向导已失效。'], 403);
        }

        $validated = Validator::make($request->all(), $this->databaseRules());
        if ($validated->fails()) {
            return response()->json(['ok' => false, 'message' => implode(' ', $validated->errors()->all())]);
        }

        $data = $validated->validated();

        try {
            $pdo = $this->connectMysql($data['host'], (int) $data['port'], $data['username'], (string) ($data['password'] ?? ''));
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }

        try {
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        } catch (\Throwable) {
            $version = '未知版本';
        }

        // 目标库存在性
        $statement = $pdo->prepare('SHOW DATABASES LIKE ?');
        $statement->execute([$data['database']]);
        $exists = (bool) $statement->fetchColumn();

        $message = 'MySQL '.$version.' 连接成功。';
        $message .= $exists
            ? '数据库「'.$data['database'].'」已存在，将直接在其中创建数据表。'
            : '数据库「'.$data['database'].'」不存在，安装时将自动创建（utf8mb4）。';

        return response()->json(['ok' => true, 'message' => $message, 'version' => $version]);
    }

    /**
     * 提交数据库配置：写 .env + APP_KEY + 生产加固 + 自动建库 + 迁移
     */
    public function database(Request $request): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        $validated = Validator::make($request->all(), $this->databaseRules());

        if ($validated->fails()) {
            return $this->backToDatabase($request, $validated->errors()->all());
        }

        $data = $validated->validated();

        try {
            $this->applyDatabaseConfig($data);
            $this->ensureAppKey();
            $this->hardenEnv();

            // 迁移（--force：生产环境免确认）
            // Artisan::call 失败默认不抛异常（返回非 0 退出码），必须显式检查——
            // 否则迁移失败也会跳到第 4 步，管理员创建时才炸出难懂错误
            $exit = Artisan::call('migrate', ['--force' => true]);
            if ($exit !== 0) {
                throw new RuntimeException('数据库迁移失败：'.trim(Artisan::output()));
            }
        } catch (\Throwable $e) {
            return $this->backToDatabase($request, [$e->getMessage()]);
        }

        return redirect()->route('install.admin');
    }

    /**
     * 数据库表单验证规则（MySQL 唯一）
     *
     * @return array<string, list<string>>
     */
    protected function databaseRules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            // 库名限制字符集：库名会拼入 CREATE DATABASE（反引号包裹 + 此处白名单双保险）
            'database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * server 级 PDO 连接（不指定库）：凭据/网络错误在迁移前直接暴露
     *
     * @throws RuntimeException 连接失败时抛出中文友好信息
     */
    protected function connectMysql(string $host, int $port, string $username, string $password): \PDO
    {
        if (! extension_loaded('pdo_mysql')) {
            throw new RuntimeException('PHP 缺少 pdo_mysql 扩展：请在 PHP 中安装/启用 pdo_mysql（宝塔：软件商店 → PHP → 安装扩展）后刷新重试');
        }

        try {
            return new \PDO(
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
    }

    /**
     * 写入 .env 并让当前进程立即生效
     *
     * @param  array<string, mixed>  $data
     */
    protected function applyDatabaseConfig(array $data): void
    {
        // 1) .env 写入（MySQL 唯一驱动）
        $this->env->write('DB_CONNECTION', 'mysql');
        foreach (['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username'] as $envKey => $field) {
            $this->env->write($envKey, (string) ($data[$field] ?? ''));
        }
        $this->env->write('DB_PASSWORD', (string) ($data['password'] ?? ''));

        // 2) 当前进程立即生效（覆盖缓存的旧连接参数）
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql' => array_merge(config('database.connections.mysql') ?? [], [
            'host' => $data['host'],
            'port' => (int) $data['port'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => (string) ($data['password'] ?? ''),
        ])]);

        DB::purge();

        // 3) 连接预检 + 目标库就绪（缺失自动创建）
        $this->ensureMysqlDatabase();

        try {
            DB::connection('mysql')->getPdo();
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
        $port = (int) ($cfg['port'] ?? 3306);
        $database = (string) ($cfg['database'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');

        // connectMysql 内部已把连接异常翻译为中文 RuntimeException
        $pdo = $this->connectMysql($host, $port, $username, $password);

        // 库名已由验证规则限制为 [A-Za-z0-9_-]，反引号包裹 + 剔除反引号双保险
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
                || str_contains($raw, 'No such file or directory')
                => '无法连接 MySQL 服务器：请检查主机地址与端口是否正确、MySQL 是否在运行、防火墙/安全组是否放行。原始错误：'.$raw,
            str_contains($raw, 'Unknown database')
                || str_contains($raw, '[1049]')
                => '数据库不存在且无法自动创建。请先手动建库后重试。原始错误：'.$raw,
            str_contains($raw, 'could not find driver')
                => 'PHP 缺少 pdo_mysql 数据库驱动扩展。原始错误：'.$raw,
            default => '数据库连接失败：'.$raw,
        };
    }

    /* 步骤 4：站点信息 + 管理员 -------------------------------------------- */

    public function showAdmin(Request $request): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        if (! Schema::hasTable('users')) {
            return redirect()->route('install.database');
        }

        // 站点 URL 默认取当前访问地址
        $host = (string) $request->getHost();
        $defaultUrl = $host !== '' ? $request->getScheme().'://'.$host : (string) config('app.url');

        return view('install', [
            'step' => 'admin',
            'old' => [],
            'errors' => [],
            'defaultUrl' => $defaultUrl,
            'defaultName' => 'Monit 网站分析',
        ]);
    }

    public function admin(Request $request): View|RedirectResponse
    {
        if (InstallState::installed()) {
            return redirect('/');
        }

        // 互斥锁（非阻塞）：installed() 检查与 InstallState::complete() 落锁之间存在
        // TOCTOU 窗口——部署窗口期攻击者并发抢注可创建第二个 type=1 超管。
        // 持锁请求串行化，其余并发请求直接拒绝；请求结束时 PHP 自动关闭句柄并释放锁，
        // 失败重试路径无需手动解锁
        $mutex = @fopen(storage_path('framework/install-mutex.lock'), 'c');
        if ($mutex === false || ! flock($mutex, LOCK_EX | LOCK_NB)) {
            return $this->backToAdmin($request, ['安装正在进行中，请勿重复提交。']);
        }

        $validated = Validator::make($request->all(), [
            'site_name' => ['required', 'string', 'min:2', 'max:64'],
            'site_url' => ['required', 'url', 'max:255'],
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'site_name.required' => '请填写网站名称',
            'site_name.min' => '网站名称至少 2 个字符',
            'site_url.required' => '请填写网站地址',
            'site_url.url' => '网站地址格式不正确（需以 http:// 或 https:// 开头）',
            'name.required' => '请填写管理员名称',
            'email.required' => '请填写管理员邮箱',
            'email.email' => '管理员邮箱格式不正确',
            'password.required' => '请填写管理员密码',
            'password.min' => '管理员密码至少 8 位',
            'password.confirmed' => '两次输入的密码不一致',
        ]);

        if ($validated->fails()) {
            return $this->backToAdmin($request, $validated->errors()->all());
        }

        if (! Schema::hasTable('users')) {
            return redirect()->route('install.database');
        }

        $data = $validated->validated();

        // 核心初始数据先就位：free/pro 套餐 + 平台默认设置（不含任何演示账号）
        $exit = Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CoreDataSeeder', '--force' => true]);
        if ($exit !== 0) {
            return $this->backToAdmin($request, ['初始数据写入失败：'.trim(Artisan::output())]);
        }

        // Seeder 直写 settings 表：清掉本进程可能残留的设置缓存（ensure 首页/后台立即可见新设置）
        Settings::flush();

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

        // 站点信息写入平台设置（seed 的默认值之上覆盖）：
        // 必须走 Settings::set（写库 + flush 双缓存）——直接 Setting::updateOrCreate 会让
        // 进程静态缓存 + Cache('monit.settings') 残留旧值最长 12h，装完后台显示默认名
        // 键对齐：branding.site_name / main.site_title 是 Brand::name() 的实际读取链，
        // site_name / site_url 供完成页与旧代码读取；APP_NAME 同步 .env
        try {
            Settings::set('site_name', $data['site_name']);
            Settings::set('site_url', rtrim($data['site_url'], '/'));
            Settings::set('branding.site_name', $data['site_name']);
            Settings::set('main.site_title', $data['site_name']);
        } catch (\Throwable $e) {
            return $this->backToAdmin($request, ['站点信息写入失败：'.$e->getMessage()]);
        }

        // APP_URL 以用户填写为准（密码重置/邮件/静态资源链接依赖）；APP_NAME 同步站点名
        $this->env->write('APP_URL', rtrim($data['site_url'], '/'));
        $this->env->write('APP_NAME', $data['site_name']);
        config(['app.url' => rtrim($data['site_url'], '/'), 'app.name' => $data['site_name']]);

        // 自动下载 GeoIP 库（缺失时国家/城市维度全部显示"未知"，安装后即开箱可用）
        // 静默执行：网络不通或磁盘不足不影响安装完成，用户可稍后手动 geoip:update
        Artisan::call('geoip:update');

        // 写入演示数据（热图 + 会话回放，让安装后即可体验完整功能）
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoHeatmapReplaySeeder', '--force' => true]);

        // 写入安装锁：此后向导失效
        InstallState::complete();

        // 若存在旧 config 缓存（空 APP_KEY / 旧 DB_* 固化），清除使其重新读取 .env
        Artisan::call('config:clear');

        return redirect()->route('install.finish');
    }

    /* 步骤 5：完成 --------------------------------------------------------- */

    public function finish(): View|RedirectResponse
    {
        if (! InstallState::installed()) {
            return redirect()->route('install');
        }

        // 完成页含管理员邮箱/数据库名：仅安装完成后的短窗口内可访问（安装锁记录完成时刻），
        // 过期即 302 首页——否则管理员信息永久对匿名访客暴露（邮箱 = 用户枚举/钓鱼辅助）
        $completedAt = trim((string) @file_get_contents(InstallState::lockPath()));

        try {
            $withinWindow = $completedAt !== ''
                && Carbon::parse($completedAt)->addMinutes(15)->isFuture();
        } catch (\Throwable) {
            $withinWindow = false; // 锁内容异常（CLI 部署兜底无锁文件）→ 保守跳转
        }

        if (! $withinWindow) {
            return redirect('/');
        }

        $admin = User::where('type', 1)->orderBy('user_id')->first();

        return view('install', [
            'step' => 'finish',
            'adminEmail' => $admin?->email ?? '',
            'siteName' => (string) (Settings::get('site_name') ?? config('app.name')),
            'siteUrl' => (string) (Settings::get('site_url') ?? config('app.url')),
            'dbDatabase' => (string) config('database.connections.mysql.database'),
        ]);
    }

    /* 辅助 ---------------------------------------------------------------- */

    /**
     * .env 不存在时从 .env.example 复制模板
     */
    protected function ensureEnvFile(): void
    {
        $envPath = $this->env->path();
        if (! file_exists($envPath) && file_exists(base_path('.env.example'))) {
            copy(base_path('.env.example'), $envPath);
        }
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

        // 当前进程立即生效
        config(['app.env' => 'production', 'app.debug' => false]);
    }

    /**
     * 无 Session 环境：错误直接回渲染第 3 步页面
     *
     * @param  list<string>  $errors
     */
    protected function backToDatabase(Request $request, array $errors): View
    {
        return view('install', [
            'step' => 'database',
            'old' => $request->only(['host', 'port', 'database', 'username']),
            'errors' => $errors,
        ]);
    }

    /**
     * 无 Session 环境：错误直接回渲染第 4 步页面
     *
     * @param  list<string>  $errors
     */
    protected function backToAdmin(Request $request, array $errors): View
    {
        $host = (string) $request->getHost();
        $defaultUrl = (string) ($request->input('site_url')
            ?: ($host !== '' ? $request->getScheme().'://'.$host : config('app.url')));

        return view('install', [
            'step' => 'admin',
            'old' => $request->only(['site_name', 'site_url', 'name', 'email']),
            'errors' => $errors,
            'defaultUrl' => $defaultUrl,
            'defaultName' => (string) ($request->input('site_name') ?: 'Monit 网站分析'),
        ]);
    }
}

