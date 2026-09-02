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
        'geetest' => [
            'prefix' => 'geetest',
            'verify' => 'https://api.geetest.com/validate.php',
            'register' => 'https://api.geetest.com/register.php',
            'field' => 'geetest_validate',
            'script' => 'https://static.geetest.com/static/tools/gt.js',
            'widget' => 'geetest-widget',
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
     * 服务端校验用户响应
     *
     * geetest v3：token 为 JSON（geetest_challenge/geetest_validate/geetest_seccode 三字段），
     * 单独走 verifyGeetest；其余三家通用 token + secret POST。
     */
    public static function verify(?string $token): bool
    {
        $provider = self::provider();

        if ($provider === null || empty($token)) {
            return false;
        }

        $meta = self::PROVIDERS[$provider];

        try {
            if ($provider === 'geetest') {
                return self::verifyGeetest($token);
            }

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

        if ($provider === 'geetest') {
            return self::geetestWidget();
        }

        $meta = self::PROVIDERS[$provider];
        $siteKey = e(self::siteKey($provider));

        $html = '<div class="'.$meta['widget'].'" data-sitekey="'.$siteKey.'"></div>';
        $html .= '<script src="'.$meta['script'].'" async defer></script>';

        return $html;
    }

    /**
     * 请求中提取验证 token（按供应商字段名）
     *
     * geetest：三字段打包为 JSON 字符串（verify 再解码）
     */
    public static function tokenFrom(array $input): ?string
    {
        $provider = self::provider();

        if ($provider === null) {
            return null;
        }

        if ($provider === 'geetest') {
            $payload = [
                'geetest_challenge' => trim((string) ($input['geetest_challenge'] ?? '')),
                'geetest_validate' => trim((string) ($input['geetest_validate'] ?? '')),
                'geetest_seccode' => trim((string) ($input['geetest_seccode'] ?? '')),
            ];

            return $payload['geetest_validate'] !== '' ? json_encode($payload) : null;
        }

        $field = self::PROVIDERS[$provider]['field'];

        return isset($input[$field]) ? trim((string) $input[$field]) : null;
    }

    /**
     * Geetest v3 注册负载（GET /captcha/geetest/register）：
     * 服务端预注册 challenge 并以私钥加盐（md5），前端 initGeetest 直用；
     * 注册接口不可达时返回 offline=true 降级负载（fail-back 模式）。
     */
    public static function geetestRegisterPayload(): array
    {
        $gt = self::siteKey('geetest');
        $key = self::secretKey('geetest');

        if ($gt === null || $key === null) {
            return ['success' => 0, 'gt' => (string) $gt, 'challenge' => '', 'offline' => true];
        }

        try {
            $response = Http::timeout(5)->get(self::PROVIDERS['geetest']['register'], [
                'gt' => $gt,
                'json_format' => 1,
                'digestmod' => 'md5',
                'client_type' => 'web',
            ]);

            $challenge = (string) ($response->json('challenge') ?? '');

            if ($response->failed() || ! preg_match('/^[a-f0-9]{32}$/', $challenge)) {
                throw new \RuntimeException('geetest register failed');
            }

            return ['success' => 1, 'gt' => $gt, 'challenge' => md5($challenge.$key), 'offline' => false];
        } catch (\Throwable) {
            // fail-back：极验注册接口不可达时走离线降级（前端 offline 模式 + 服务端本地 md5 校验）
            return ['success' => 0, 'gt' => $gt, 'challenge' => md5(uniqid('', true).$key), 'offline' => true];
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * Geetest v3 二次校验：
     * 1) 官方防伪：md5(seccode) === validate
     * 2) 在线：validate.php 校验 seccode；网络异常按 fail-open 放行（与其它供应商一致）
     * 3) 离线降级负载（offline 注册）：md5 防伪通过即视为通过
     */
    protected static function verifyGeetest(string $token): bool
    {
        $payload = json_decode($token, true);

        if (! is_array($payload)) {
            return false;
        }

        $challenge = (string) ($payload['geetest_challenge'] ?? '');
        $validate = (string) ($payload['geetest_validate'] ?? '');
        $seccode = (string) ($payload['geetest_seccode'] ?? '');

        if ($challenge === '' || $validate === '' || $seccode === '') {
            return false;
        }

        if (md5($seccode) !== $validate) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post(self::PROVIDERS['geetest']['verify'], [
                    'seccode' => $seccode,
                    'json_format' => 1,
                    'challenge' => $challenge,
                    'captchaid' => self::siteKey('geetest'),
                    'sdk' => 'php-laravel',
                ]);

            return ($response->json('status') ?? '') === 'success';
        } catch (\Throwable) {
            // 极验服务超时/不可达：md5 防伪已通过，可用性优先放行
            return true;
        }
    }

    /**
     * Geetest v3 前端片段：占位容器 + 异步注册 + initGeetest + 成功回填隐藏字段
     */
    protected static function geetestWidget(): string
    {
        $registerUrl = e(route('captcha.geetest.register'));

        return <<<HTML
<div id="geetest-widget"></div>
<script>
(function () {
    var widgetEl = document.getElementById('geetest-widget');
    if (!widgetEl) { return; }
    function loadGt(cb) {
        if (window.initGeetest) { return cb(); }
        var s = document.createElement('script');
        s.src = 'https://static.geetest.com/static/tools/gt.js';
        s.onload = cb;
        document.head.appendChild(s);
    }
    function fill(form, validate) {
        ['geetest_challenge', 'geetest_validate', 'geetest_seccode'].forEach(function (key) {
            var input = form.querySelector('input[name="' + key + '"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                form.appendChild(input);
            }
            input.value = validate[key] || '';
        });
    }
    fetch('{$registerUrl}', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            loadGt(function () {
                window.initGeetest({
                    gt: data.gt,
                    challenge: data.challenge,
                    offline: data.offline === true,
                    new_captcha: true,
                    product: 'popup',
                    lang: document.documentElement.lang === 'zh-cn' ? 'zh-cn' : 'en'
                }, function (captchaObj) {
                    var form = widgetEl.closest('form');
                    if (form) { captchaObj.appendTo(widgetEl); }
                    captchaObj.onSuccess(function () {
                        if (form) { fill(form, captchaObj.getValidate()); }
                    });
                });
            });
        })
        .catch(function () { /* 注册失败：静默，提交时服务端按空 token 拒绝 */ });
})();
</script>
HTML;
    }

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
