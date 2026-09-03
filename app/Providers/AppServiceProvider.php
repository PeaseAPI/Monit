<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Website;
use App\Support\PluginManager;
use App\Support\Settings;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 反向代理信任（config/monit.php trusted_proxies，.env TRUSTED_PROXIES）：
        // 必须在 provider boot 阶段设置——此时 .env/config 已加载、TrustProxies
        // 中间件尚未执行；bootstrap/app.php 的 withMiddleware 闭包过早（kernel
        // 构造期）只能拿到 env() 默认值
        $proxies = strtolower(trim((string) config('monit.trusted_proxies', 'private')));
        TrustProxies::at(match (true) {
            $proxies === '*' => '*',
            $proxies === 'none' => [],
            $proxies === 'private' => ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7'],
            default => array_values(array_filter(array_map('trim', explode(',', $proxies)))),
        });

        // SMTP 设置桥（后台 设置→SMTP → Laravel mail 运行时配置）
        // smtp_host 非空即启用 settings 驱动的 SMTP；留空回落 .env（MAIL_MAILER 默认 log）
        $this->applySmtpSettings();
        // 网站所有权检查（路由 can:own,website）
        // 支持两种调用方式：
        //   1. Route Model Binding 传入 Website 模型
        //   2. 路由参数直接传入整数 ID（此时自动查询）
        Gate::define('own', function (User $user, mixed $website): bool {
            if (! $website instanceof Website) {
                $website = Website::findOrFail((int) $website);
            }

            return (int) $user->user_id === (int) $website->user_id || $user->isAdmin();
        });

        // 启动已激活插件（规格书 §14：init.php 注册路由 / 指令 / 监听器）
        try {
            PluginManager::boot();
        } catch (\Throwable $e) {
            report($e);
        }

        // Email Shield 插件（规格 §14.9）：@email_shield 指令输出混淆邮箱
        Blade::directive('email_shield', function (string $expression): string {
            return "<?php echo app(\\App\\Services\\EmailShieldService::class)->obfuscate({$expression}); ?>";
        });
        Blade::directive('email_shield_link', function (string $expression): string {
            return "<?php echo app(\\App\\Services\\EmailShieldService::class)->link({$expression}); ?>";
        });
    }

    /**
     * SMTP 设置桥：settings smtp.* → config('mail')（对标原版后台 SMTP 表单）
     *
     * 后台填写的 SMTP 参数运行时覆盖 .env，管理员无需改配置文件即可切换发信通道。
     * 消费键：smtp_host/port/encryption/username/password/auth/from_email/from_name/
     *         reply_to/reply_to_name/cc/bcc
     */
    protected function applySmtpSettings(): void
    {
        try {
            $host = trim((string) Settings::get('smtp.smtp_host', ''));

            if ($host === '') {
                return;
            }

            $port = (int) (Settings::get('smtp.smtp_port') ?: 587);
            $encryption = strtolower(trim((string) Settings::get('smtp.smtp_encryption', 'tls')));
            $username = trim((string) Settings::get('smtp.smtp_username', ''));
            $password = (string) Settings::get('smtp.smtp_password', '');
            $auth = Settings::get('smtp.smtp_auth');

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp' => array_merge((array) config('mail.mailers.smtp'), [
                    'host' => $host,
                    'port' => $port,
                    'encryption' => in_array($encryption, ['tls', 'ssl', 'none', ''], true) ? $encryption : 'tls',
                    'username' => in_array($auth, [false, 'false', '0'], true) ? null : $username,
                    'password' => in_array($auth, [false, 'false', '0'], true) ? null : $password,
                ]),
            ]);

            $fromEmail = trim((string) Settings::get('smtp.smtp_from_email', ''));

            if ($fromEmail !== '') {
                config(['mail.from' => [
                    'address' => $fromEmail,
                    'name' => trim((string) Settings::get('smtp.smtp_from_name', '')) ?: config('app.name'),
                ]]);
            }

            $replyTo = trim((string) Settings::get('smtp.smtp_reply_to', ''));

            if ($replyTo !== '') {
                config(['mail.reply_to' => [
                    'address' => $replyTo,
                    'name' => trim((string) Settings::get('smtp.smtp_reply_to_name', '')),
                ]]);
            }

            foreach (['cc' => 'smtp_cc', 'bcc' => 'smtp_bcc'] as $key => $setting) {
                $value = trim((string) Settings::get('smtp.'.$setting, ''));

                if ($value !== '') {
                    config(['mail.'.$key => array_filter(array_map('trim', explode(',', $value)))]);
                }
            }
        } catch (\Throwable) {
            // 安装向导阶段 settings 表可能不存在：静默回落 .env 配置
        }
    }
}
