<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * 人机验证统一出口（对标原版 captcha 设置组）
 *
 * 关联：
 * - 数据来源：settings captcha.*（后台 设置→人机验证 维护）
 * - 消费方：AuthController（登录/注册）、ForgotPasswordController（找回密码）、IndexController（联系表单）
 * - 供应商：recaptcha(v2) / hcaptcha / turnstile 三家任选其一
 *
 * 设计原则：场景开关（captcha_on_*）+ 全局类型/密钥统一读取；
 * 未配置密钥时场景自动降级为关闭，绝不阻塞登录主流程。
 */
class Captcha
{
    /** 场景 → 设置开关键 */
    public const SCENES = [
        'register' => 'captcha_on_register',
        'login' => 'captcha_on_login',
        'lost_password' => 'captcha_on_lost_password',
        'contact' => 'captcha_on_contact',
    ];

    /** 供应商 → [设置前缀, 校验端点, 响应字段, 站点脚本] */
    protected const PROVIDERS = [
        'recaptcha' => [
            'prefix' => 'recaptcha',
            'verify' => 'https://www.google.com/recaptcha/api/siteverify',
            'field' => 'g-recaptcha-response',
            'script' => 'https://www.google.com/recaptcha/api.js',
            'widget' => 'g-recaptcha',
        ],
        'hcaptcha' => [
            'prefix' => 'hcaptcha',
            'verify' => 'https://api.hcaptcha.com/siteverify',
            'field' => 'h-captcha-response',
            'script' => 'https://js.hcaptcha.com/1/api.js',
            'widget' => 'h-captcha',
        ],
        'turnstile' => [
            'prefix' => 'turnstile',
            'verify' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            'field' => 'cf-turnstile-response',
            'script' => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
            'widget' => 'cf-turnstile',
        ],
    ];

    /**
     * 当前启用的供应商（settings 类型 + 密钥齐备才生效）
     */
    public static function provider(): ?string
    {
        $type = strtolower(trim((string) Settings::get('captcha.captcha_type', '')));

        if (! isset(self::PROVIDERS[$type])) {
            return null;
        }

        return self::siteKey($type) && self::secretKey($type) ? $type : null;
    }

    /**
     * 场景是否需要人机验证（开关开启且供应商可用）
     */
    public static function enabled(string $scene): bool
    {
        if (! isset(self::SCENES[$scene]) || self::provider() === null) {
            return false;
        }

        return self::truthy(Settings::get('captcha.'.self::SCENES[$scene]));
    }

    /**
     * 服务端校验用户响应（hcaptcha/turnstile/recaptcha 通用：token + secret POST）
     */
    public static function verify(?string $token): bool
    {
        $provider = self::provider();

        if ($provider === null || empty($token)) {
            return false;
        }

        $meta = self::PROVIDERS[$provider];

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post($meta['verify'], [
                    'secret' => self::secretKey($provider),
                    'response' => $token,
                ]);

            return (bool) ($response->json('success') ?? false);
        } catch (\Throwable) {
            // 上游超时/网络异常：放行（可用性优先，与原版 fail-open 行为一致）
            return true;
        }
    }

    /**
     * 前端 widget 渲染片段（各表单 @include 时输出）
     */
    public static function widget(string $scene): string
    {
        if (! self::enabled($scene)) {
            return '';
        }

        $provider = self::provider();
        $meta = self::PROVIDERS[$provider];
        $siteKey = e(self::siteKey($provider));

        $html = '<div class="'.$meta['widget'].'" data-sitekey="'.$siteKey.'"></div>';
        $html .= '<script src="'.$meta['script'].'" async defer></script>';

        return $html;
    }

    /**
     * 请求中提取验证 token（按供应商字段名）
     */
    public static function tokenFrom(array $input): ?string
    {
        $provider = self::provider();

        if ($provider === null) {
            return null;
        }

        $field = self::PROVIDERS[$provider]['field'];

        return isset($input[$field]) ? trim((string) $input[$field]) : null;
    }

    /* ------------------------------------------------------------------ */

    protected static function siteKey(string $provider): ?string
    {
        return self::nonEmpty(Settings::get('captcha.'.self::PROVIDERS[$provider]['prefix'].'_site_key'))
            // 通用键兜底（后台只填一份时三家共享）
            ?? self::nonEmpty(Settings::get('captcha.captcha_site_key'));
    }

    protected static function secretKey(string $provider): ?string
    {
        return self::nonEmpty(Settings::get('captcha.'.self::PROVIDERS[$provider]['prefix'].'_secret_key'))
            ?? self::nonEmpty(Settings::get('captcha.captcha_secret_key'));
    }

    protected static function nonEmpty(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    protected static function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
