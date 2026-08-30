<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $settings['payment_gateways'] = app(\App\Support\EnvWriter::class)
            ->readMany(\App\Support\PaymentGatewayCatalog::keys());

        return view('admin.settings.index', compact('settings'))->with('adminNav', 'settings');
    }

    /**
     * 通用更新入口：通过 group 参数确定分组
    */
    public function update(Request $request): RedirectResponse
    {
        $group = $request->input('group');

        // 校验分组是否合法（必须存在于 allSettings 清单中）
        if (!in_array($group, array_keys($this->allSettings()), true)) {
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
            \App\Support\Settings::flush();

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

        $this->saveSettings($group, $validated);

        // 同时清 Cache 与进程内静态缓存（Settings::flush）
        \App\Support\Settings::flush();

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
     * - 空值 = 清除该键；布尔键归一为 true/false 字符串
     */
    protected function updatePaymentGateways(Request $request): RedirectResponse
    {
        $allowed = \App\Support\PaymentGatewayCatalog::keys();
        $boolKeys = \App\Support\PaymentGatewayCatalog::boolKeys();

        $rules = [];
        foreach ($allowed as $key) {
            $rules[$key] = in_array($key, $boolKeys, true)
                ? 'nullable|in:true,false,1,0'
                : 'nullable|string|max:4096';
        }

        $validated = $request->validate($rules);

        // 只处理白名单键（validate 已按规则键过滤）
        $writer = app(\App\Support\EnvWriter::class);

        foreach ($allowed as $key) {
            if (! array_key_exists($key, $validated)) {
                continue; // 未提交的键不动
            }

            $value = $validated[$key];

            if (in_array($key, $boolKeys, true)) {
                $value = in_array($value, ['true', '1'], true) ? 'true' : 'false';
            } else {
                $value = trim((string) $value);
            }

            $writer->write($key, $value);
        }

        // 写入 .env 后清理可能存在的配置缓存，使新密钥立即生效
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        } catch (\Throwable) {
            // config:clear 失败不阻断保存（无缓存环境下无害）
        }

        return back()->with('success', __('msg.settings_saved', ['group' => 'payment_gateways']));
    }

    /* --------------------------------------------------------------------- */
    /* 设置分组 & 保存                                                       */
    /* --------------------------------------------------------------------- */

    protected function allSettings(): array
    {
        return [
            'main' => $this->getGroup('main'),
            'users' => $this->getGroup('users'),
            'payment' => $this->getGroup('payment'),
            'payment_gateways' => [], // 当前值在 index() 中从 .env 读取（EnvWriter）
            'analytics' => $this->getGroup('analytics'),
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
    /* 验证规则                                                              */
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
            ],
            'smtp' => [
                'smtp_host' => 'nullable|string|max:256',
                'smtp_port' => 'nullable|integer',
                'smtp_encryption' => 'nullable|string|in:tls,ssl,none',
                'smtp_username' => 'nullable|string|max:256',
                'smtp_password' => 'nullable|string|max:256',
                'smtp_from_email' => 'nullable|email|max:256',
                'smtp_from_name' => 'nullable|string|max:256',
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
                'captcha_type' => 'nullable|string|in:recaptcha,recaptcha_v3,hcaptcha,turnstile,none',
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
            ],
            'ads' => [
                'ads_is_enabled' => 'boolean',
                'ads_header' => 'nullable|string',
                'ads_footer' => 'nullable|string',
            ],
            'announcements' => [
                'announcements_is_enabled' => 'boolean',
                'announcements_content' => 'nullable|string',
                'announcements_type' => 'nullable|string|in:info,warning,success,danger',
            ],
            'internal_notifications' => [
                'internal_notifications_is_enabled' => 'boolean',
                'internal_notifications_payment_success' => 'boolean',
                'internal_notifications_plan_expiry' => 'boolean',
                'internal_notifications_limit_reached' => 'boolean',
            ],
            'email_notifications' => [
                'email_notifications_new_user' => 'boolean',
                'email_notifications_new_payment' => 'boolean',
                'email_notifications_new_website' => 'boolean',
                'email_notifications_user_plan_expiry_reminder' => 'boolean',
            ],
            'webhooks' => [
                'start_url' => 'nullable|url',
                'end_url' => 'nullable|url',
                'webhook_payment_success_url' => 'nullable|url',
                'webhook_payment_failure_url' => 'nullable|url',
                'webhook_user_register_url' => 'nullable|url',
                'webhook_user_delete_url' => 'nullable|url',
            ],
            'theme' => [
                'theme' => 'nullable|string|max:64',
                'primary_color' => 'nullable|string|max:7',
                'secondary_color' => 'nullable|string|max:7',
                'white_label_is_enabled' => 'boolean',
            ],
            'cron' => [
                'cron_key' => 'nullable|string|max:64',
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
            ],
            'custom' => [
                'custom_head_js' => 'nullable|string',
                'custom_head_css' => 'nullable|string',
                'custom_footer_js' => 'nullable|string',
            ],
            'custom_images' => [
                'logo' => 'nullable|string|max:512',
                'favicon' => 'nullable|string|max:512',
                'og_image' => 'nullable|string|max:512',
            ],
                        'content' => [
                'index_html' => 'nullable|string',
                'terms_html' => 'nullable|string',
                'privacy_html' => 'nullable|string',
                'imprint_html' => 'nullable|string',
            ],
            'affiliate' => [
                'affiliate_is_enabled' => 'boolean',
                'affiliate_commission_percentage' => 'nullable|numeric|min:0|max:100',
                'affiliate_cookie_duration_days' => 'nullable|integer|min:1|max:365',
                'affiliate_minimum_withdrawal_amount' => 'nullable|numeric|min:0',
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
            ],
            'email_shield' => [
                'email_shield_is_enabled' => 'boolean',
            ],
            'dynamic_og_images' => [
                'dynamic_og_images_is_enabled' => 'boolean',
            ],
            'pwa' => [
                'pwa_is_enabled' => 'boolean',
                'pwa_name' => 'nullable|string|max:128',
                'pwa_short_name' => 'nullable|string|max:32',
                'pwa_description' => 'nullable|string|max:256',
                'pwa_theme_color' => 'nullable|string|max:7',
                'pwa_background_color' => 'nullable|string|max:7',
            ],
            'push_notifications' => [
                'push_notifications_is_enabled' => 'boolean',
                'push_notifications_public_key' => 'nullable|string|max:256',
                'push_notifications_private_key' => 'nullable|string|max:256',
                'push_notifications_vapid_subject' => 'nullable|string|max:256',
                'push_notifications_subscribers_limit' => 'nullable|integer|min:1',
                'push_notifications_campaigns_limit' => 'nullable|integer|min:1',
            ],
            default => [],
        };

        return $rules;
    }
}
