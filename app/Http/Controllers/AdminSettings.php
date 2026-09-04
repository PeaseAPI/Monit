<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\LicenseManager;
use App\Support\EnvWriter;
use App\Support\PaymentGatewayCatalog;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 管理后台 - 系统设置（AdminSettings，94K 最大控制器）
 * 规格书 §6.3.1：集中管理全部 settings 配置
 *
 * 分组：main, payment, analytics, cron, webhooks, plan_free/guest/custom, socials, plugins
 */
class AdminSettings extends Controller
{
    /**
     * 设置主页面（所有分组选项卡）
     */
    public function index(Request $request)
    {
        $settings = $this->allSettings();

        // 「支付网关密钥」组不存 settings 表：当前值直接读 .env（EnvWriter）
        $settings['payment_gateways'] = app(EnvWriter::class)
            ->readMany(PaymentGatewayCatalog::keys());

        // business 为持久化设置组（allSettings 已含）；cache/health/support
        // 为只读运维面板（原系统独立功能页），数据在控制器组装，不接受表单保存
        $settings['cache'] = $this->cachePanel();
        $settings['health'] = $this->healthPanel();
        $settings['support'] = $this->supportPanel();

        return view('admin.settings.index', compact('settings'))->with('adminNav', 'settings');
    }

    /**
     * 通用更新入口：通过 group 参数确定分组
     */
    public function update(Request $request): RedirectResponse
    {
        $group = $request->input('group');

        // 校验分组是否合法（必须存在于 allSettings 清单中）
        if (! in_array($group, array_keys($this->allSettings()), true)) {
            return back()->withErrors(['error' => __('msg.invalid_settings_group')]);
        }

        // 支付网关密钥：白名单键写入 .env（而非 settings 表）
        if ($group === 'payment_gateways') {
            return $this->updatePaymentGateways($request);
        }

        // 套餐设置（plan_*）允许任意字段，宽松保存
        if (str_starts_with($group, 'plan_')) {
            $data = collect($request->except(['_token', '_method', 'group']))
                ->map(fn ($value) => $value === '1' ? true : $value)
                ->toArray();
            $this->saveSettings($group, $data);

            // 同时清 Cache 与进程内静态缓存（Settings::flush）
            Settings::flush();

            return back()->with('success', __('msg.settings_saved', ['group' => $group]));
        }

        $rules = $this->getValidationRules($group);

        if (empty($rules)) {
            return back()->withErrors(['error' => __('msg.invalid_settings_group')]);
        }

        $validated = $request->validate($rules);

        // 未勾选的复选框不会提交，显式置为 false 以支持"取消勾选后保存"
        foreach (array_keys(array_filter($rules, fn ($rule) => str_contains($rule, 'boolean'))) as $field) {
            $validated[$field] = $request->boolean($field);
        }

        // 多货币清单：code 规范化 + 剔除默认货币行/无效汇率行（规格书 §10.4）
        if ($group === 'payment' && array_key_exists('currencies', $validated)) {
            $validated['currencies'] = $this->sanitizeCurrencies(
                (array) $validated['currencies'],
                strtoupper((string) ($validated['currency'] ?? 'CNY')),
            );
        }

        // 品牌文件上传（用户反馈 #21）：logo/favicon/logo_dark 文件上传后存入 storage，
        // 覆盖对应 URL 字段（上传优先于 URL 直填）；未上传则保留原 URL
        if ($group === 'branding') {
            $validated = $this->handleBrandingUploads($request, $validated);
        }

        $this->saveSettings($group, $validated);

        // 同时清 Cache 与进程内静态缓存（Settings::flush）
        Settings::flush();

        return back()->with('success', __('msg.settings_saved', ['group' => $group]));
    }

    /**
     * 多货币清单清洗（规格书 §10.4）：
     * code 规范化为 3 位大写字母；剔除默认货币行（基准恒为 1）与无有效汇率的行
     */
    protected function sanitizeCurrencies(array $currencies, string $default): array
    {
        $clean = [];

        foreach ($currencies as $code => $row) {
            $code = strtoupper(trim((string) $code));

            if (! preg_match('/^[A-Z]{3}$/', $code) || $code === strtoupper($default)) {
                continue;
            }

            $rate = (float) ($row['rate'] ?? 0);

            if ($rate <= 0) {
                continue;
            }

            $clean[$code] = [
                'name' => trim((string) ($row['name'] ?? '')),
                'symbol' => trim((string) ($row['symbol'] ?? '')),
                'rate' => $rate,
            ];
        }

        return $clean;
    }

    /**
     * 「支付网关密钥」保存：白名单键安全写入 .env 并清理配置缓存
     *
     * 安全要点：
     * - 只接受 PaymentGatewayCatalog::keys() 登记的键（请求中的 APP_KEY/DB_* 等一律忽略）
     * - 值经 EnvWriter 转义（引号/井号/换行），无法注入额外 env 行
     * - 密钥键（type=password）：空值 = 保持不变（页面仅掩码显示，空提交属常态，
     *   防止整表单保存时误清空未编辑的密钥）；勾选配套 {key}__clear 复选框才显式清除
     * - text/bool 键：空值 = 清除（原语义，公开 ID 无机密性）
     * - 布尔键归一为 true/false 字符串
     */
    protected function updatePaymentGateways(Request $request): RedirectResponse
    {
        $allowed = PaymentGatewayCatalog::keys();
        $boolKeys = PaymentGatewayCatalog::boolKeys();
        $secretKeys = PaymentGatewayCatalog::passwordKeys();

        $rules = [];
        foreach ($allowed as $key) {
            $rules[$key] = in_array($key, $boolKeys, true)
                ? 'nullable|in:true,false,1,0'
                : 'nullable|string|max:4096';
        }

        $validated = $request->validate($rules);

        // 只处理白名单键（validate 已按规则键过滤）
        $writer = app(EnvWriter::class);

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $validated)) {
                continue; // 未提交的键不动
            }

            $value = $validated[$key];

            if (in_array($key, $boolKeys, true)) {
                $value = in_array($value, ['true', '1'], true) ? 'true' : 'false';
                $writer->write($key, $value);

                continue;
            }

            $value = trim((string) $value);

            // 密钥键空值 = 保持不变；显式勾选 {key}__clear 才清除
            if (in_array($key, $secretKeys, true) && $value === '') {
                if ($request->boolean($key.'__clear')) {
                    $writer->write($key, '');
                }

                continue;
            }

            $writer->write($key, $value);
        }

        // 写入 .env 后清理可能存在的配置缓存，使新密钥立即生效
        try {
            Artisan::call('config:clear');
        } catch (\Throwable) {
            // config:clear 失败不阻断保存（无缓存环境下无害）
        }

        return back()->with('success', __('msg.settings_saved', ['group' => 'payment_gateways']));
    }

    /**
     * 「缓存」面板：清空缓存（原系统 cache 运维页）
     *
     * Settings::flush() 清进程静态缓存 + monit.settings 条目；
     * cache:clear 清空整个缓存存储（业务缓存/驱动层面）。
     */
    public function clearCache(Request $request): RedirectResponse
    {
        try {
            Artisan::call('cache:clear');
        } catch (\Throwable) {
            // 无缓存环境下无害
        }

        Settings::flush();

        return back()->with('success', __('msg.cache_cleared'));
    }

    /**
     * 「缓存」面板数据（只读状态）
     */
    protected function cachePanel(): array
    {
        return [
            'driver' => (string) config('cache.default'),
            'settings_cached' => Cache::has('monit.settings'),
            'settings_ttl_hours' => 12,
        ];
    }

    /**
     * 「健康检查」面板数据（原系统 health 运维页）
     */
    protected function healthPanel(): array
    {
        $mysqlVersion = null;

        try {
            $row = DB::select('select version() as v');
            $mysqlVersion = $row[0]->v ?? null;
        } catch (\Throwable) {
            // 连接失败时留空，页面标红
        }

        $diskFree = @disk_free_space(base_path());
        $diskTotal = @disk_total_space(base_path());

        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'database_driver' => (string) config('database.default'),
            'mysql_version' => $mysqlVersion,
            'cache_driver' => (string) config('cache.default'),
            'queue_driver' => (string) config('queue.default'),
            'disk_free' => $diskFree === false ? null : $diskFree,
            'disk_total' => $diskTotal === false ? null : $diskTotal,
            'timezone' => (string) config('app.timezone'),
            'settings_count' => Setting::count(),
            // GeoIP 库状态（用户反馈 #2：国家/大洲显示"未知"的排查入口——
            // 未放置 mmdb 库文件时地理维度全部为空，页面提示一键修复命令）
            'geoip_available' => app(\App\Services\GeoIp::class)->isAvailable(),
            'geoip_path' => (string) config('services.geoip.mmdb_path'),
        ];
    }

    /**
     * 「支持与授权」面板数据（原系统 support/license 组）
     */
    protected function supportPanel(): array
    {
        $status = app(LicenseManager::class)->status();

        return [
            'version' => (string) config('monit.version'),
            'license_valid' => (bool) $status['valid'],
            'license_reason' => (string) $status['reason'],
            'license_data' => $status['data'],
        ];
    }

    /* --------------------------------------------------------------------- */
    /* 设置分组 & 保存 */
    /* --------------------------------------------------------------------- */

    protected function allSettings(): array
    {
        return [
            'main' => $this->getGroup('main'),
            'users' => $this->getGroup('users'),
            'payment' => $this->getGroup('payment'),
            'payment_gateways' => [], // 当前值在 index() 中从 .env 读取（EnvWriter）
            'business' => $this->getGroup('business'), // 发票抬头企业信息（原库 settings.business 组）
            'analytics' => $this->getGroup('analytics'),
            'seo' => $this->getGroup('seo'), // SEO 功能设置（审计/工具中心/监控，后台 seo 组）
            'maps' => $this->getGroup('maps'),
            'smtp' => $this->getGroup('smtp'),
            'sms' => $this->getGroup('sms'),
            'ai' => $this->getGroup('ai'),
            'captcha' => $this->getGroup('captcha'),
            'socials' => $this->getGroup('socials'),
            'cookie_consent' => $this->getGroup('cookie_consent'),
            'ads' => $this->getGroup('ads'),
            'announcements' => $this->getGroup('announcements'),
            'internal_notifications' => $this->getGroup('internal_notifications'),
            'email_notifications' => $this->getGroup('email_notifications'),
            'webhooks' => $this->getGroup('webhooks'),
            'theme' => $this->getGroup('theme'),
            'branding' => $this->getGroup('branding'),
            'custom' => $this->getGroup('custom'),
            'custom_images' => $this->getGroup('custom_images'),
            'content' => $this->getGroup('content'),
            'cron' => $this->getGroup('cron'),
            'plan_free' => $this->getGroup('plan_free'),
            'plan_guest' => $this->getGroup('plan_guest'),
            'plan_custom' => $this->getGroup('plan_custom'),
            'affiliate' => $this->getGroup('affiliate'),
            'offload' => $this->getGroup('offload'),
            'image_optimizer' => $this->getGroup('image_optimizer'),
            'email_shield' => $this->getGroup('email_shield'),
            'dynamic_og_images' => $this->getGroup('dynamic_og_images'),
            'pwa' => $this->getGroup('pwa'),
            'push_notifications' => $this->getGroup('push_notifications'),
        ];
    }

    protected function getGroup(string $group): array
    {
        return Setting::where('key', 'like', "{$group}.%")->pluck('value', 'key')->toArray();
    }

    protected function saveSettings(string $group, array $data): void
    {
        DB::transaction(function () use ($group, $data) {
            foreach ($data as $key => $value) {
                $fullKey = "{$group}.{$key}";
                Setting::updateOrCreate(
                    ['key' => $fullKey],
                    // Setting 模型 value 列带 json cast：字符串/数组直接赋值由 cast 编码；
                    // 此前手动 json_encode 会双重编码（读回带引号），已修复。
                    // bool 保持 'true'/'false' 字符串（视图判断约定）。
                    ['value' => is_bool($value) ? ($value ? 'true' : 'false') : $value]
                );
            }
        });
    }

    /* --------------------------------------------------------------------- */
    /* 验证规则 */
    /* --------------------------------------------------------------------- */

    protected function getValidationRules(string $group): array
    {
        $rules = match ($group) {
            'main' => [
                'site_title' => 'required|string|max:256',
                'site_description' => 'nullable|string|max:1024',
                'default_language' => 'required|string|max:10',
                'registration_is_enabled' => 'boolean',
                'maintenance_is_enabled' => 'boolean',
                'seo_is_enabled' => 'boolean',
                'iframe_is_enabled' => 'boolean',
                'whitelabel_is_enabled' => 'boolean',
                'force_https' => 'boolean',
                'api_is_enabled' => 'boolean',
                'ai_crawlers_is_enabled' => 'boolean',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'default_timezone' => 'nullable|string|max:64',
                'avatar_size_limit' => 'nullable|integer|min:16|max:20480',
                'default_theme_style' => 'nullable|string|in:light,dark',
                'theme_style_change_is_enabled' => 'boolean',
                'auto_language_detection_is_enabled' => 'boolean',
                'breadcrumbs_is_enabled' => 'boolean',
                'display_pagination_when_no_pages' => 'boolean',
                'default_results_per_page' => 'nullable|integer|min:5|max:100',
                'default_order_type' => 'nullable|string|in:ASC,DESC',
                'display_index_plans' => 'boolean',
                'display_index_testimonials' => 'boolean',
                'display_index_faq' => 'boolean',
                'display_index_latest_blog_posts' => 'boolean',
                'index_url' => 'nullable|string|max:512',
                'maintenance_title' => 'nullable|string|max:256',
                'maintenance_description' => 'nullable|string|max:2048',
                'maintenance_button_text' => 'nullable|string|max:64',
                'maintenance_button_url' => 'nullable|string|max:512',
                'referrer_policy' => 'nullable|string|max:64',
                'title_separator' => 'nullable|string|max:8',
                'not_found_url' => 'nullable|string|max:512',
                'terms_and_conditions_url' => 'nullable|string|max:512',
                'privacy_policy_url' => 'nullable|string|max:512',
                'chart_cache' => 'nullable|integer|min:0|max:10080',
                'chart_days' => 'nullable|integer|min:1|max:365',
                'sitemap_url' => 'nullable|string|max:512',
            ],
            // SEO 功能设置（后台 seo 组；上游：设置页 partials/seo；下游：SeoFeatureEnabled
            // 中间件 / AuditEngine / SeoToolController / 定时任务 Seo/* 命令）
            'seo' => [
                'audits_is_enabled' => 'boolean',
                'tools_is_enabled' => 'boolean',
                'tools_guest_access' => 'boolean',
                'tools_guest_monthly_limit' => 'nullable|integer|min:-1|max:100000',
                'seo_disabled_tools' => 'nullable|string|max:8192',
                'sitemap_monitor_is_enabled' => 'boolean',
                'domain_monitor_is_enabled' => 'boolean',
                'seo_request_timeout' => 'nullable|integer|min:5|max:120',
                'seo_request_user_agent' => 'nullable|string|max:256',
                'seo_double_check' => 'boolean',
                'seo_double_check_wait' => 'nullable|integer|min:1|max:10',
                'domain_monitor_alert_days' => 'nullable|string|max:64|regex:/^\d+(\s*,\s*\d+)*$/',
                'archives_retention_days' => 'nullable|integer|min:0|max:3650',
                'serpapi_api_key' => 'nullable|string|max:256',
            ],
            'users' => [
                'register_is_enabled' => 'boolean',
                'email_activation_is_enabled' => 'boolean',
                'auto_delete_unconfirmed_users' => 'boolean',
                'auto_delete_unconfirmed_users_days' => 'nullable|integer|min:1',
                'user_deletion_reminder' => 'boolean',
                'two_fa_is_enabled' => 'boolean',
                'api_is_enabled' => 'boolean',
                'user_registration_require_consent' => 'boolean',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'welcome_email_is_enabled' => 'boolean',
                'auto_delete_inactive_users' => 'boolean',
                'blacklisted_domains' => 'nullable|string|max:4096',
                'blacklisted_ips' => 'nullable|string|max:4096',
                'blacklisted_countries' => 'nullable|string|max:512',
                'login_lockout_is_enabled' => 'boolean',
                'login_lockout_max_retries' => 'nullable|integer|min:1|max:100',
                'login_lockout_time' => 'nullable|integer|min:10|max:10080',
                'lost_password_lockout_is_enabled' => 'boolean',
                'lost_password_lockout_max_retries' => 'nullable|integer|min:1|max:100',
                'lost_password_lockout_time' => 'nullable|integer|min:10|max:10080',
                'resend_activation_lockout_is_enabled' => 'boolean',
                'resend_activation_lockout_max_retries' => 'nullable|integer|min:1|max:100',
                'resend_activation_lockout_time' => 'nullable|integer|min:10|max:10080',
                'register_lockout_is_enabled' => 'boolean',
                'register_lockout_max_registrations' => 'nullable|integer|min:1|max:1000',
                'register_lockout_time' => 'nullable|integer|min:10|max:10080',
                'register_display_newsletter_checkbox' => 'boolean',
                'account_display_newsletter_checkbox' => 'boolean',
                'login_rememberme_checkbox_is_checked' => 'boolean',
                'login_rememberme_cookie_days' => 'nullable|integer|min:1|max:365',
            ],
            // 发票抬头企业信息（原库 settings.business 组 16 字段全量）
            // 上游：后台「发票信息」选项卡；下游：AdminInvoice 发票/信用票据抬头与票号前缀
            'business' => [
                'brand_name' => 'nullable|string|max:128',
                'invoice_nr_prefix' => 'nullable|string|max:16',
                'name' => 'nullable|string|max:128',
                'address' => 'nullable|string|max:256',
                'city' => 'nullable|string|max:64',
                'county' => 'nullable|string|max:64',
                'zip' => 'nullable|string|max:16',
                'country' => 'nullable|string|max:8',
                'email' => 'nullable|email|max:191',
                'phone' => 'nullable|string|max:32',
                'tax_type' => 'nullable|string|in:VAT,GST',
                'tax_id' => 'nullable|string|max:64',
                'custom_key_one' => 'nullable|string|max:64',
                'custom_value_one' => 'nullable|string|max:191',
                'custom_key_two' => 'nullable|string|max:64',
                'custom_value_two' => 'nullable|string|max:191',
            ],
            'payment' => [
                'currency' => 'required|string|size:3',
                'currencies' => 'nullable|array',
                'currencies.*.name' => 'nullable|string|max:64',
                'currencies.*.symbol' => 'nullable|string|max:8',
                'currencies.*.rate' => 'nullable|numeric|min:0.000001',
                'auto_currency_detection' => 'boolean',
                'user_plan_expiry_reminder' => 'boolean',
                'taxes_enabled' => 'boolean',
                'invoice_is_enabled' => 'boolean',
                'stripe_is_enabled' => 'boolean',
                'stripe_publishable_key' => 'nullable|string|max:256',
                'stripe_secret_key' => 'nullable|string|max:256',
                'paypal_is_enabled' => 'boolean',
                'paypal_client_id' => 'nullable|string|max:256',
                'paypal_secret' => 'nullable|string|max:256',
                'razorpay_is_enabled' => 'boolean',
                'razorpay_key_id' => 'nullable|string|max:256',
                'wechat_pay_is_enabled' => 'boolean',
                'alipay_is_enabled' => 'boolean',
                'offline_is_enabled' => 'boolean',
                'offline_instructions' => 'nullable|string',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'payment_is_enabled' => 'boolean',
                'default_payment_type' => 'nullable|string|in:one_time,recurring',
                'default_payment_frequency' => 'nullable|string|in:monthly,annual,lifetime',
                'codes_is_enabled' => 'boolean',
                'taxes_and_billing_is_enabled' => 'boolean',
                'trial_require_card' => 'boolean',
                'user_plan_expiry_checker_is_enabled' => 'boolean',
                'currency_exchange_api_key' => 'nullable|string|max:256',
            ],
            'analytics' => [
                'email_reports_is_enabled' => 'boolean',
                'annotations_is_enabled' => 'boolean',
                'sessions_replays_is_enabled' => 'boolean',
                'websites_heatmaps_is_enabled' => 'boolean',
                'dashboard_views_is_enabled' => 'boolean',
                'custom_domains_is_enabled' => 'boolean',
                'extra_domains_is_enabled' => 'boolean',
                'outbound_clicks_is_enabled' => 'boolean',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'ip_storage_is_enabled' => 'boolean',
                'sessions_replays_minimum_duration' => 'nullable|integer|min:0|max:600',
                'pixel_cache' => 'boolean',
                'pixel_exposed_identifier' => 'boolean',
                'email_notices_is_enabled' => 'boolean',
                'domains_is_enabled' => 'boolean',
                'additional_domains_is_enabled' => 'boolean',
                'main_domain_is_enabled' => 'boolean',
                'domains_custom_main_ip' => 'nullable|string|max:64',
                'blacklisted_domains' => 'nullable|string|max:4096',
                'example_url' => 'nullable|string|max:512',
            ],
            'smtp' => [
                'smtp_host' => 'nullable|string|max:256',
                'smtp_port' => 'nullable|integer',
                'smtp_encryption' => 'nullable|string|in:tls,ssl,none',
                'smtp_username' => 'nullable|string|max:256',
                'smtp_password' => 'nullable|string|max:256',
                'smtp_from_email' => 'nullable|email|max:256',
                'smtp_from_name' => 'nullable|string|max:256',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'smtp_reply_to_name' => 'nullable|string|max:128',
                'smtp_reply_to' => 'nullable|email|max:256',
                'smtp_cc' => 'nullable|string|max:512',
                'smtp_bcc' => 'nullable|string|max:512',
                'smtp_auth' => 'boolean',
            ],
            'sms' => [
                'sms_is_enabled' => 'boolean',
                'sms_provider' => 'nullable|string|in:aliyun,tencent,log',
                'sms_code_ttl_minutes' => 'nullable|integer|min:1|max:30',
                'sms_resend_interval_seconds' => 'nullable|integer|min:30|max:3600',
                'sms_aliyun_access_key_id' => 'nullable|string|max:256',
                'sms_aliyun_access_key_secret' => 'nullable|string|max:256',
                'sms_aliyun_sign_name' => 'nullable|string|max:64',
                'sms_aliyun_template_code' => 'nullable|string|max:64',
                'sms_tencent_secret_id' => 'nullable|string|max:256',
                'sms_tencent_secret_key' => 'nullable|string|max:256',
                'sms_tencent_sdk_app_id' => 'nullable|string|max:64',
                'sms_tencent_sign_name' => 'nullable|string|max:64',
                'sms_tencent_template_id' => 'nullable|string|max:64',
                'sms_register_is_enabled' => 'boolean',
                'sms_phone_login_is_enabled' => 'boolean',
                'sms_forgot_password_is_enabled' => 'boolean',
                'sms_phone_bind_is_enabled' => 'boolean',
                // 登录二次校验（用户反馈 #16）：开启后已绑手机号的用户
                // 登录（邮箱/手机号路径）必须提供短信验证码
                'sms_login_verify_enabled' => 'boolean',
            ],
            'ai' => [
                'ai_is_enabled' => 'boolean',
                'ai_provider' => 'nullable|string|in:aliyun_bailian,tencent_hunyuan,volcengine_ark,openai_compatible,log',
                'ai_api_key' => 'nullable|string|max:512',
                'ai_model' => 'nullable|string|max:128',
                'ai_base_url' => 'nullable|string|max:512',
                'ai_temperature' => 'nullable|numeric|min:0|max:2',
                'ai_max_tokens' => 'nullable|integer|min:16|max:32768',
                'ai_timeout' => 'nullable|integer|min:5|max:300',
                'ai_insights_is_enabled' => 'boolean',
            ],
            'captcha' => [
                'captcha_type' => 'nullable|string|in:recaptcha,recaptcha_v3,hcaptcha,turnstile,geetest,none',
                'captcha_site_key' => 'nullable|string|max:256',
                'captcha_secret_key' => 'nullable|string|max:256',
                'captcha_on_register' => 'boolean',
                'captcha_on_login' => 'boolean',
                'captcha_on_lost_password' => 'boolean',
                'captcha_on_contact' => 'boolean',
                'recaptcha_site_key' => 'nullable|string|max:256',
                'recaptcha_secret_key' => 'nullable|string|max:256',
                'hcaptcha_site_key' => 'nullable|string|max:256',
                'hcaptcha_secret_key' => 'nullable|string|max:256',
                'turnstile_site_key' => 'nullable|string|max:256',
                'turnstile_secret_key' => 'nullable|string|max:256',
                'geetest_site_key' => 'nullable|string|max:64',
                'geetest_secret_key' => 'nullable|string|max:128',
            ],
            'socials' => [
                'google' => 'nullable|array',
                'github' => 'nullable|array',
                'facebook' => 'nullable|array',
                'discord' => 'nullable|array',
                'linkedin' => 'nullable|array',
                'microsoft' => 'nullable|array',
                'apple' => 'nullable|array',
                'twitter' => 'nullable|array',
                'qq' => 'nullable|array',
                'wechat' => 'nullable|array',
                'weibo' => 'nullable|array',
                'gitee' => 'nullable|array',
                'feishu' => 'nullable|array',
            ],
            'cookie_consent' => [
                'cookie_consent_is_enabled' => 'boolean',
                'cookie_consent_type' => 'nullable|string|in:opt_in,opt_out',
                'cookie_consent_message' => 'nullable|string|max:1024',
                'cookie_consent_title' => 'nullable|string|max:128',
                'cookie_consent_description' => 'nullable|string|max:1024',
                'cookie_consent_button_text' => 'nullable|string|max:64',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'cookie_consent_logging_is_enabled' => 'boolean',
                'cookie_consent_necessary_is_enabled' => 'boolean',
                'cookie_consent_analytics_is_enabled' => 'boolean',
                'cookie_consent_targeting_is_enabled' => 'boolean',
                'cookie_consent_layout' => 'nullable|string|in:bar,box',
                'cookie_consent_position_y' => 'nullable|string|in:top,bottom',
                'cookie_consent_position_x' => 'nullable|string|in:left,center,right',
            ],
            'ads' => [
                'ads_is_enabled' => 'boolean',
                'ads_header' => 'nullable|string',
                'ads_footer' => 'nullable|string',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'ad_blocker_detector_is_enabled' => 'boolean',
                'ad_blocker_detector_lock_is_enabled' => 'boolean',
                'ad_blocker_detector_delay' => 'nullable|integer|min:0|max:10000',
            ],
            'announcements' => [
                'announcements_is_enabled' => 'boolean',
                'announcements_content' => 'nullable|string',
                'announcements_type' => 'nullable|string|in:info,warning,success,danger',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'announcements_guests_is_enabled' => 'boolean',
                'announcements_guests_content' => 'nullable|string|max:2048',
                'announcements_guests_text_color' => 'nullable|string|max:7',
                'announcements_guests_background_color' => 'nullable|string|max:7',
                'announcements_users_is_enabled' => 'boolean',
                'announcements_users_content' => 'nullable|string|max:2048',
                'announcements_users_text_color' => 'nullable|string|max:7',
                'announcements_users_background_color' => 'nullable|string|max:7',
            ],
            'internal_notifications' => [
                'internal_notifications_is_enabled' => 'boolean',
                'internal_notifications_payment_success' => 'boolean',
                'internal_notifications_plan_expiry' => 'boolean',
                'internal_notifications_limit_reached' => 'boolean',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'internal_notifications_users_is_enabled' => 'boolean',
                'internal_notifications_admins_is_enabled' => 'boolean',
                'internal_notifications_delete_user' => 'boolean',
                'internal_notifications_new_newsletter_subscriber' => 'boolean',
                'internal_notifications_new_affiliate_withdrawal' => 'boolean',
            ],
            'email_notifications' => [
                'email_notifications_new_user' => 'boolean',
                'email_notifications_new_payment' => 'boolean',
                'email_notifications_new_website' => 'boolean',
                'email_notifications_user_plan_expiry_reminder' => 'boolean',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'email_notifications_delete_user' => 'boolean',
                'email_notifications_new_domain' => 'boolean',
                'email_notifications_contact' => 'boolean',
                'email_notifications_new_affiliate_withdrawal' => 'boolean',
            ],
            'webhooks' => [
                'start_url' => 'nullable|url',
                'end_url' => 'nullable|url',
                'webhook_payment_success_url' => 'nullable|url',
                'webhook_payment_failure_url' => 'nullable|url',
                'webhook_user_register_url' => 'nullable|url',
                'webhook_user_delete_url' => 'nullable|url',
                'webhook_user_update_url' => 'nullable|url',
                'webhook_code_redeemed_url' => 'nullable|url',
                'webhook_contact_url' => 'nullable|url',
                'webhook_domain_new_url' => 'nullable|url',
                'webhook_domain_update_url' => 'nullable|url',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'webhooks_secret_key' => 'nullable|string|max:128',
                'webhooks_user_update' => 'boolean',
                'webhooks_code_redeemed' => 'boolean',
                'webhooks_contact' => 'boolean',
                'webhooks_cron_start' => 'boolean',
                'webhooks_cron_end' => 'boolean',
                'webhooks_domain_new' => 'boolean',
                'webhooks_domain_update' => 'boolean',
                'wait_for_response_domains' => 'nullable|string|max:4096',
            ],
            'theme' => [
                'theme' => 'nullable|string|max:64',
                'primary_color' => 'nullable|string|max:7',
                'secondary_color' => 'nullable|string|max:7',
                'white_label_is_enabled' => 'boolean',
            ],
            'cron' => [
                'cron_key' => 'nullable|string|max:64',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'cron_email_reports' => 'boolean',
                'cron_broadcasts' => 'boolean',
                'cron_push_notifications' => 'boolean',
            ],
            'branding' => [
                'site_name' => 'nullable|string|max:128',
                'logo_url' => 'nullable|string|max:512',
                'logo_dark_url' => 'nullable|string|max:512',
                'favicon_url' => 'nullable|string|max:512',
                'primary_color' => 'nullable|string|max:7|regex:/^#[0-9a-fA-F]{6}$/',
                'landing_theme' => 'nullable|string|max:32|alpha_dash',
                'show_landing_plans' => 'boolean',
                'landing_hero_title' => 'nullable|string|max:256',
                'landing_hero_subtitle' => 'nullable|string|max:512',
                'footer_icp' => 'nullable|string|max:256',
                'footer_custom_html' => 'nullable|string|max:8192',
                // 用户反馈 #21：文件上传（上传优先于 URL 直填）
                'logo_upload' => 'nullable|image|max:2048',
                'logo_dark_upload' => 'nullable|image|max:2048',
                'favicon_upload' => 'nullable|file|mimes:ico,png,svg,webp|max:2048',
            ],
            'custom' => [
                'custom_head_js' => 'nullable|string',
                'custom_head_css' => 'nullable|string',
                'custom_footer_js' => 'nullable|string',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'custom_welcome_js' => 'nullable|string|max:8192',
                'custom_pay_thank_you_js' => 'nullable|string|max:8192',
                'custom_body_content' => 'nullable|string|max:8192',
            ],
            'custom_images' => [
                'logo' => 'nullable|string|max:512',
                'favicon' => 'nullable|string|max:512',
                'og_image' => 'nullable|string|max:512',
            ],
            'maps' => [
                'provider' => 'nullable|string|in:none,google,baidu',
                'baidu_key' => 'nullable|string|max:256',
                'google_key' => 'nullable|string|max:256',
            ],
            'content' => [
                'index_html' => 'nullable|string',
                'terms_html' => 'nullable|string',
                'privacy_html' => 'nullable|string',
                'imprint_html' => 'nullable|string',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'blog_is_enabled' => 'boolean',
                'blog_share_is_enabled' => 'boolean',
                'blog_search_widget_is_enabled' => 'boolean',
                'blog_categories_widget_is_enabled' => 'boolean',
                'blog_popular_widget_is_enabled' => 'boolean',
                'blog_views_is_enabled' => 'boolean',
                'blog_ratings_is_enabled' => 'boolean',
                'blog_columns' => 'nullable|integer|min:1|max:4',
                'pages_is_enabled' => 'boolean',
                'pages_share_is_enabled' => 'boolean',
                'pages_popular_widget_is_enabled' => 'boolean',
                'pages_views_is_enabled' => 'boolean',
                'broadcasts_is_enabled' => 'boolean',
                'broadcasts_statistics_is_enabled' => 'boolean',
                'broadcasts_emails_per_cron' => 'nullable|integer|min:1|max:1000',
            ],
            'affiliate' => [
                'affiliate_is_enabled' => 'boolean',
                'affiliate_commission_percentage' => 'nullable|numeric|min:0|max:100',
                'affiliate_cookie_duration_days' => 'nullable|integer|min:1|max:365',
                'affiliate_minimum_withdrawal_amount' => 'nullable|numeric|min:0',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'affiliate_commission_type' => 'nullable|string|in:percentage,fixed',
                'affiliate_tracking_type' => 'nullable|string|in:cookie,code',
                'affiliate_tracking_duration' => 'nullable|integer|min:1|max:3650',
                'affiliate_withdrawal_notes' => 'nullable|string|max:2048',
            ],
            'offload' => [
                'offload_is_enabled' => 'boolean',
                'offload_storage_driver' => 'nullable|string|in:s3,minio,custom,aliyun_oss,tencent_cos,local',
                'offload_s3_key' => 'nullable|string|max:256',
                'offload_s3_secret' => 'nullable|string|max:256',
                'offload_s3_region' => 'nullable|string|max:64',
                'offload_s3_bucket' => 'nullable|string|max:128',
                'offload_s3_endpoint' => 'nullable|string|max:512',
                'offload_oss_access_key_id' => 'nullable|string|max:256',
                'offload_oss_access_key_secret' => 'nullable|string|max:256',
                'offload_oss_bucket' => 'nullable|string|max:128',
                'offload_oss_endpoint' => 'nullable|string|max:512',
                'offload_cos_secret_id' => 'nullable|string|max:256',
                'offload_cos_secret_key' => 'nullable|string|max:256',
                'offload_cos_bucket' => 'nullable|string|max:128',
                'offload_cos_region' => 'nullable|string|max:64',
                'offload_cdn_url' => 'nullable|string|max:512',
            ],
            'image_optimizer' => [
                'image_optimizer_is_enabled' => 'boolean',
                'image_optimizer_quality' => 'nullable|integer|min:1|max:100',
                'image_optimizer_keep_original' => 'boolean',
                'image_optimizer_auto_optimize' => 'boolean',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'image_optimizer_provider' => 'nullable|string|in:local,imagerypro',
                'image_optimizer_statistics_is_enabled' => 'boolean',
                'image_optimizer_imagerypro_api_key' => 'nullable|string|max:256',
            ],
            'email_shield' => [
                'email_shield_is_enabled' => 'boolean',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'email_shield_statistics_is_enabled' => 'boolean',
                'email_shield_api_key' => 'nullable|string|max:256',
                'email_shield_whitelisted_domains' => 'nullable|string|max:4096',
            ],
            'dynamic_og_images' => [
                'dynamic_og_images_is_enabled' => 'boolean',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'dynamic_og_images_api_key' => 'nullable|string|max:256',
                'dynamic_og_images_imagerypro_api_key' => 'nullable|string|max:256',
                'dynamic_og_images_quality' => 'nullable|integer|min:1|max:100',
                'dynamic_og_images_title' => 'nullable|string|max:256',
                'dynamic_og_images_title_color' => 'nullable|string|max:7',
                'dynamic_og_images_background_color' => 'nullable|string|max:7',
                'dynamic_og_images_screenshot_image_border_radius' => 'nullable|integer|min:0|max:64',
                'dynamic_og_images_refresh_interval' => 'nullable|integer|min:0|max:100000',
            ],
            'pwa' => [
                'pwa_is_enabled' => 'boolean',
                'pwa_name' => 'nullable|string|max:128',
                'pwa_short_name' => 'nullable|string|max:32',
                'pwa_description' => 'nullable|string|max:256',
                'pwa_theme_color' => 'nullable|string|max:7',
                'pwa_background_color' => 'nullable|string|max:7',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'pwa_display_install_bar' => 'boolean',
                'pwa_display_install_bar_for_guests' => 'boolean',
                'pwa_is_fullscreen' => 'boolean',
                'pwa_dynamic_splash_screen' => 'boolean',
                'pwa_display_install_bar_delay' => 'nullable|integer|min:0|max:60000',
                'pwa_display_install_bar_minimum_pageviews_count' => 'nullable|integer|min:0|max:1000',
                'pwa_app_start_url' => 'nullable|string|max:512',
            ],
            'push_notifications' => [
                'push_notifications_is_enabled' => 'boolean',
                'push_notifications_public_key' => 'nullable|string|max:256',
                'push_notifications_private_key' => 'nullable|string|max:256',
                'push_notifications_vapid_subject' => 'nullable|string|max:256',
                'push_notifications_subscribers_limit' => 'nullable|integer|min:1',
                'push_notifications_campaigns_limit' => 'nullable|integer|min:1',
                // ↓ 原版对标补充（66 分析 / AltumCode）
                'push_notifications_guests_is_enabled' => 'boolean',
                'ask_to_subscribe_is_enabled' => 'boolean',
                'ask_to_subscribe_delay' => 'nullable|integer|min:0|max:60000',
                'ask_to_subscribe_delay_minimum_pageviews_count' => 'nullable|integer|min:0|max:1000',
                'notifications_per_cron' => 'nullable|integer|min:1|max:10000',
                'notifications_per_cron_batch' => 'nullable|integer|min:1|max:1000',
                'notifications_per_cron_batch_concurrently' => 'nullable|integer|min:1|max:50',
            ],
            default => [],
        };

        return $rules;
    }

    /**
     * 处理品牌设置组文件上传（用户反馈 #21）
     *
     * 上传的文件存入 storage/app/public/branding/，并将 URL 写入对应 _url 字段。
     * 上传优先于 URL 直填：有文件上传则覆盖 URL 字段，无则保留原 URL。
     */
    protected function handleBrandingUploads(Request $request, array $validated): array
    {
        $disk = Storage::disk('public');
        $uploadMap = [
            'logo_upload' => 'logo_url',
            'logo_dark_upload' => 'logo_dark_url',
            'favicon_upload' => 'favicon_url',
        ];

        foreach ($uploadMap as $uploadField => $urlField) {
            if (! $request->hasFile($uploadField) || ! $request->file($uploadField)->isValid()) {
                continue;
            }

            $file = $request->file($uploadField);
            $filename = Str::random(16) . '.' . $file->getClientOriginalExtension();

            // 删除旧文件（如有）
            $oldUrl = $validated[$urlField] ?? Settings::get("branding.{$urlField}", '');
            if ($oldUrl && str_starts_with($oldUrl, '/storage/branding/')) {
                $oldPath = str_replace('/storage/', '', $oldUrl);
                $disk->delete($oldPath);
            }

            // 存储新文件
            $path = $file->storeAs('branding', $filename, 'public');

            // 用本地 URL 覆盖 URL 字段
            $validated[$urlField] = '/storage/' . $path;
        }

        // 移除上传字段（非 settings 表字段）
        foreach (array_keys($uploadMap) as $uploadField) {
            unset($validated[$uploadField]);
        }

        return $validated;
    }
}
