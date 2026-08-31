<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Web 安装向导（规格书 §15.3：安装器本地化，§19 部署）
 * 三步：环境检查 → 数据库 → 管理员账户
 * 安装完成后写入 storage/installed.lock，路由即失效。
 */
class InstallController extends Controller
{
    protected function lockFile(): string
    {
        return storage_path('installed.lock');
    }

    protected function installed(): bool
    {
        if (file_exists($this->lockFile())) {
            return true;
        }

        // 保险：已有管理员账户（如 dev 环境或升级部署）视为已安装
        try {
            return Schema::hasTable('users')
                && User::where('type', 1)->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public function index(Request $request)
    {
        if ($this->installed()) {
            return redirect('/');
        }

        $envPath = base_path('.env');
        if (! file_exists($envPath) && file_exists(base_path('.env.example'))) {
            copy(base_path('.env.example'), $envPath);
        }

        $checks = [
            'php_version' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'pdo' => extension_loaded('pdo'),
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'mbstring' => extension_loaded('mbstring'),
            'gd' => extension_loaded('gd'),
            'storage_writable' => is_writable(storage_path()),
            'env_file' => file_exists($envPath),
        ];

        return view('install', [
            'step' => 'requirements',
            'checks' => $checks,
            'allPassed' => ! in_array(false, $checks, true),
        ]);
    }

    /**
     * 步骤 2：数据库配置 + 迁移
     */
    public function database(Request $request): RedirectResponse
    {
        if ($this->installed()) {
            return redirect('/');
        }

        $validated = $request->validate([
            'connection' => ['required', 'in:sqlite,mysql'],
            'host' => ['required_if:connection,mysql', 'nullable', 'string'],
            'port' => ['required_if:connection,mysql', 'nullable', 'integer'],
            'database' => ['required_if:connection,mysql', 'nullable', 'string'],
            'username' => ['required_if:connection,mysql', 'nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $this->setEnv('DB_CONNECTION', $validated['connection']);

        if ($validated['connection'] === 'mysql') {
            foreach (['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username'] as $envKey => $field) {
                $this->setEnv($envKey, (string) ($validated[$field] ?? ''));
            }
            $this->setEnv('DB_PASSWORD', (string) ($validated['password'] ?? ''));
        } else {
            // sqlite 默认 database/database.sqlite
            if (! file_exists(database_path('database.sqlite'))) {
                file_put_contents(database_path('database.sqlite'), '');
            }
            $this->setEnv('DB_DATABASE', database_path('database.sqlite'));
        }

        // 生成 APP_KEY
        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // 迁移
        Artisan::call('migrate', ['--force' => true]);

        return redirect()->route('install.admin');
    }

    /**
     * 步骤 3：创建管理员
     */
    public function showAdmin(Request $request)
    {
        if ($this->installed()) {
            return redirect('/');
        }

        return view('install', ['step' => 'admin']);
    }

    public function admin(Request $request): RedirectResponse
    {
        if ($this->installed()) {
            return redirect('/');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Schema::hasTable('users')) {
            return back()->withErrors(['database' => '数据库尚未迁移，请返回上一步。']);
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'type' => 1,
            'status' => 1,
            'plan_id' => 'custom',
            'email_verified_at' => now(),
        ]);

        // 写入基础设置
        Settings::set('main.title', 'Monit');
        Settings::set('main.maintenance_is_enabled', 'false');
        Settings::set('users.registration_is_enabled', 'true');
        Settings::set('payment.currency_code', 'USD');

        file_put_contents($this->lockFile(), now()->toDateTimeString());

        // 清理缓存配置
        Artisan::call('config:clear');

        return redirect()->route('login')->with('success', '安装完成，请登录管理员账户。');
    }

    /**
     * 修改/追加 .env 键
     * 值经转义后写入：含空白/引号/井号/换行的值（如数据库密码）用双引号包裹并转义，
     * 防止通过密码字段注入额外 env 键（如伪造 APP_KEY）
     */
    protected function setEnv(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if ($value !== '' && preg_match('/[\s"\'#\\\\]/', $value)) {
            $value = '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        if (! file_exists($envPath)) {
            file_put_contents($envPath, $key.'='.$value."\n");

            return;
        }

        $content = (string) file_get_contents($envPath);

        if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $content)) {
            $content = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $key.'='.$value, $content);
        } else {
            $content .= $key.'='.$value."\n";
        }

        file_put_contents($envPath, $content);
    }
}
