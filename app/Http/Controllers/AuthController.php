<?php

namespace App\Http\Controllers;

use App\Mail\ActivateUser;
use App\Mail\WelcomeUser;
use App\Models\AccountLog;
use App\Models\User;
use App\Services\Sms\SmsService;
use App\Services\TotpService;
use App\Services\UserAgentParser;
use App\Services\WebhookService;
use App\Support\Captcha;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Monit 认证（中文优先）
 * MVP：邮箱+密码注册/登录/登出（邮箱激活、社交登录见 Phase 2/3）
 */
class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('auth.login', [
            'phoneLoginEnabled' => SmsService::scenarioEnabled('phone_login'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        // 人机验证（captcha.captcha_on_login）：失败直接打回
        if (Captcha::enabled('login') && ! Captcha::verify(Captcha::tokenFrom($request->all()))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['captcha' => __('validation.captcha_failed')]);
        }

        // 手机号登录（M17 §12.5）：开关开启且输入为手机号时走手机号流程（密码或短信验证码）
        $identifier = trim((string) $request->input('email', ''));

        if (SmsService::scenarioEnabled('phone_login') && SmsService::isPhone($identifier)) {
            return $this->loginByPhone($request, SmsService::normalizePhone($identifier));
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => __('validation.email_required'),
            'email.email' => __('validation.email_email'),
            'password.required' => __('validation.password_required'),
        ]);

        $remember = $request->boolean('remember');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('validation.auth_failed')]);
        }

        return $this->completeLogin($request, $user, $remember);
    }

    /**
     * 手机号登录（M17 §12.5）：手机号 + 密码，或手机号 + 短信验证码（免密码）
     */
    protected function loginByPhone(Request $request, string $phone): RedirectResponse
    {
        $request->validate([
            'password' => ['nullable', 'string'],
            'sms_code' => ['nullable', 'digits:6'],
        ], [
            'sms_code.digits' => __('auth.sms_code_invalid'),
        ]);

        $user = User::where('phone', $phone)->first();

        // 短信验证码登录（免密码）
        if ($request->filled('sms_code')) {
            if (! $user || ! SmsService::verify($phone, 'login', (string) $request->input('sms_code'))) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['sms_code' => __('auth.sms_code_invalid')]);
            }
        } else {
            if (! $user || ! $request->filled('password') || ! Hash::check((string) $request->input('password'), $user->password)) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('validation.auth_failed')]);
            }
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'));
    }

    /**
     * 登录收尾：状态检查 → 2FA → 建立登录态 → 日志
     */
    protected function completeLogin(Request $request, User $user, bool $remember): RedirectResponse
    {
        if ($user->status !== 1) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('validation.account_disabled')]);
        }

        // 两步验证（规格书 §12.4）：平台开关 users.two_fa_is_enabled 开启且用户已启用时进入二步验证流程
        if ($user->twofa_is_enabled && self::twoFaEnabled()) {
            $request->session()->put([
                'twofa_user_id' => $user->user_id,
                'twofa_remember' => $remember,
                'twofa_expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route('login.twofa');
        }

        // remember-me Cookie 有效期（users.login_rememberme_cookie_days）
        Auth::guard('web')->setRememberDuration(self::rememberLifetimeMinutes());

        Auth::login($user, $remember);

        $user->forceFill([
            'last_activity' => now(),
            'total_logins' => $user->total_logins + 1,
        ])->save();

        $this->logAccount($user, 'login');

        return redirect()->intended(route('dashboard'));
    }

    /**
     * 两步验证页（规格书 §12.4）
     */
    public function showTwoFactor(Request $request)
    {
        if (! $request->session()->has('twofa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.twofa');
    }

    /**
     * 两步验证提交
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        if (! self::twoFaEnabled()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => __('account.twofa_code_required'),
            'code.digits' => __('account.twofa_code_invalid'),
        ]);

        $userId = $request->session()->get('twofa_user_id');
        $expiresAt = $request->session()->get('twofa_expires_at');

        if (! $userId || ($expiresAt && now()->timestamp > $expiresAt)) {
            $request->session()->forget(['twofa_user_id', 'twofa_remember', 'twofa_expires_at']);

            return redirect()->route('login')->withErrors(['email' => __('account.twofa_expired')]);
        }

        $user = User::find($userId);

        // 一次性消费：同一窗口的码登录后不可复用（RFC 6238 §5.2，防钓鱼重放）
        if (! $user || ! $user->twofa_is_enabled
            || ! TotpService::consume((string) $user->twofa_token, $validated['code'], "user.{$user->user_id}")) {
            return back()->withErrors(['code' => __('account.twofa_code_invalid')]);
        }

        $request->session()->forget(['twofa_user_id', 'twofa_remember', 'twofa_expires_at']);

        Auth::guard('web')->setRememberDuration(self::rememberLifetimeMinutes());
        Auth::login($user, $request->session()->get('twofa_remember', false));

        $user->forceFill([
            'last_activity' => now(),
            'total_logins' => $user->total_logins + 1,
        ])->save();

        $this->logAccount($user, 'login_2fa');

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(Request $request)
    {
        // 注册总开关（main.registration_is_enabled）：关闭时前台不可达
        if (! self::registrationEnabled()) {
            return redirect()->route('login')->withErrors(['email' => __('auth.registration_disabled')]);
        }

        // 如果 URL 中有推荐码，保存到 session
        if ($ref = $request->query('ref')) {
            session(['referral_key' => $ref]);
        }

        return view('auth.register', [
            'smsRegisterEnabled' => SmsService::scenarioEnabled('register'),
            'requireConsent' => self::requireConsent(),
            'termsUrl' => self::termsUrl(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        // 注册总开关（main.registration_is_enabled）：关闭时拒绝提交
        if (! self::registrationEnabled()) {
            return redirect()->route('login')->withErrors(['email' => __('auth.registration_disabled')]);
        }

        // 人机验证（captcha.captcha_on_register）
        if (Captcha::enabled('register') && ! Captcha::verify(Captcha::tokenFrom($request->all()))) {
            return back()->withErrors(['captcha' => __('validation.captcha_failed')]);
        }

        // 短信验证注册（M17 §12.5）：开关开启时需手机号 + 短信验证码
        $smsRegister = SmsService::scenarioEnabled('register');

        $rules = [
            'name' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
        ];

        if ($smsRegister) {
            $rules['phone'] = ['required', 'string', 'regex:/^1[3-9]\d{9}$/', 'unique:users,phone'];
            $rules['sms_code'] = ['required', 'digits:6'];
        }

        // 条款同意（users.user_registration_require_consent）：开启时必须勾选
        if (self::requireConsent()) {
            $rules['terms'] = ['accepted'];
        }

        $validated = $request->validate($rules, [
            'name.required' => __('validation.name_required'),
            'email.required' => __('validation.email_required'),
            'email.email' => __('validation.email_email'),
            'email.unique' => __('validation.email_unique'),
            'password.required' => __('validation.password_required'),
            'password.min' => __('validation.password_min'),
            'phone.required' => __('validation.phone_required'),
            'phone.regex' => __('validation.phone_invalid'),
            'phone.unique' => __('auth.phone_taken'),
            'sms_code.required' => __('validation.sms_code_required'),
            'sms_code.digits' => __('auth.sms_code_invalid'),
            'terms.accepted' => __('auth.terms_required'),
        ]);

        // 注册黑名单（后台 设置→用户：域名 / IP，原版 blacklisted_*）
        $emailDomain = strtolower(substr(strrchr($validated['email'], '@'), 1) ?: '');
        $blacklistedDomains = array_filter(preg_split('/\r\n|\r|\n/', (string) Settings::get('users.blacklisted_domains', '')));
        $blacklistedDomains = array_map(fn ($d) => strtolower(trim($d)), $blacklistedDomains);

        if ($emailDomain && in_array($emailDomain, $blacklistedDomains, true)) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'sms_code']))
                ->withErrors(['email' => __('auth.email_domain_blacklisted')]);
        }

        $clientIp = $request->ip();
        $blacklistedIps = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) Settings::get('users.blacklisted_ips', ''))));

        if ($clientIp && in_array($clientIp, $blacklistedIps, true)) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'sms_code']))
                ->withErrors(['email' => __('auth.registration_blocked')]);
        }

        // 国家黑名单（users.blacklisted_countries，ISO-3166 alpha-2 逗号分隔）
        // 国家来源：CF-IPCountry 请求头（Cloudflare）→ 无来源时跳过检测
        if ($blockedCountries = self::blacklistedCountries()) {
            $country = strtoupper(trim((string) $request->header('CF-IPCountry', '')));

            if ($country !== '' && in_array($country, $blockedCountries, true)) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation', 'sms_code']))
                    ->withErrors(['email' => __('auth.registration_blocked')]);
            }
        }

        // 短信验证码校验（一次性）
        $phone = null;

        if ($smsRegister) {
            $phone = SmsService::normalizePhone($validated['phone']);

            if (! SmsService::verify($phone, 'register', (string) $validated['sms_code'])) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation', 'sms_code']))
                    ->withErrors(['sms_code' => __('auth.sms_code_invalid')]);
            }
        }

        // 处理推荐码（规格书 §14.7：?ref=XXXXX 绑定推荐人）
        $referredBy = null;
        if ($ref = $request->input('ref') ?? session('referral_key')) {
            $referrer = User::where('referral_key', $ref)->first();
            if ($referrer) {
                $referredBy = $referrer->user_id;
            }
        }

        // 邮箱激活（users.email_activation_is_enabled）：开启时注册即待激活并发送激活邮件
        $requireActivation = filter_var(Settings::get('users.email_activation_is_enabled'), FILTER_VALIDATE_BOOLEAN);
        $activationCode = $requireActivation ? Str::random(64) : null;

        $user = User::create([
            'type' => 0,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $phone,
            'phone_verified_at' => $phone ? now() : null,
            'plan_id' => 'free',
            'referral_key' => Str::random(32),
            'api_key' => Str::random(60),
            'language' => self::defaultLanguage(),
            'timezone' => self::defaultTimezone(),
            'status' => $requireActivation ? 0 : 1,
            'email_activation_code' => $activationCode,
            'ip' => $request->ip(),
            'source' => 'direct',
            'referred_by' => $referredBy,
        ]);

        $this->logAccount($user, 'register');

        // 平台 Webhook：用户注册（规格 §6.3.1：webhooks.webhook_user_register_url）
        app(WebhookService::class)->userRegister([
            'user_id' => $user->user_id,
            'email' => $user->email,
            'name' => $user->name,
            'referred_by' => $referredBy,
        ]);

        if ($requireActivation) {
            Mail::to($user->email)->send(
                new ActivateUser($user, route('activation.activate', $activationCode))
            );

            return redirect()->route('activation.sent');
        }

        Auth::login($user);

        // 欢迎邮件（users.welcome_email_is_enabled）：免激活注册立即发送
        if (self::welcomeEmailEnabled()) {
            try {
                Mail::to($user->email)->send(new WelcomeUser($user));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('welcome_email_failed', ['error' => $e->getMessage()]);
            }
        }

        $user->forceFill(['last_activity' => now(), 'total_logins' => 1])->save();

        return redirect()->route('dashboard')
            ->with('success', __('msg.welcome_monit'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->logAccount($user, 'logout');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function logAccount(User $user, string $type): void
    {
        $parser = UserAgentParser::make($user->last_activity ? request()->userAgent() : request()->userAgent());
        [$osName] = $parser->os();
        [$browserName] = $parser->browser();

        AccountLog::create([
            'user_id' => $user->user_id,
            'type' => $type,
            'ip' => request()->ip(),
            'device_type' => $parser->deviceType(),
            'os_name' => $osName,
            'browser_name' => $browserName,
            'datetime' => now(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* 设置驱动的平台开关（原版对标：main/users 组） */
    /* ------------------------------------------------------------------ */

    /** 注册总开关 main.registration_is_enabled（默认开启） */
    public static function registrationEnabled(): bool
    {
        return self::on(Settings::get('main.registration_is_enabled'), default: true);
    }

    /** 两步验证平台开关 users.two_fa_is_enabled（默认开启：用户已配置的 2FA 不因后台关闭而失效） */
    public static function twoFaEnabled(): bool
    {
        return self::on(Settings::get('users.two_fa_is_enabled'), default: true);
    }

    /** 注册条款勾选 users.user_registration_require_consent */
    public static function requireConsent(): bool
    {
        return self::on(Settings::get('users.user_registration_require_consent'), default: false);
    }

    /** 欢迎邮件开关 users.welcome_email_is_enabled（默认开启） */
    public static function welcomeEmailEnabled(): bool
    {
        return self::on(Settings::get('users.welcome_email_is_enabled'), default: true);
    }

    /** 条款链接 main.terms_and_conditions_url（无外部链接时回退站内 /terms） */
    public static function termsUrl(): string
    {
        $url = trim((string) Settings::get('main.terms_and_conditions_url', ''));

        return $url !== '' ? $url : route('terms');
    }

    /** 新用户默认语言 main.default_language（回退 zh_CN） */
    public static function defaultLanguage(): string
    {
        $language = trim((string) Settings::get('main.default_language', ''));

        return array_key_exists($language, (array) config('monit.locales')) ? $language : 'zh_CN';
    }

    /** 新用户默认时区 main.default_timezone（回退 Asia/Shanghai） */
    public static function defaultTimezone(): string
    {
        $timezone = trim((string) Settings::get('main.default_timezone', ''));

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Asia/Shanghai';
    }

    /** 国家黑名单 users.blacklisted_countries（逗号分隔 ISO alpha-2） */
    public static function blacklistedCountries(): array
    {
        $raw = (string) Settings::get('users.blacklisted_countries', '');

        return array_values(array_filter(array_map(
            fn ($c) => strtoupper(trim($c)),
            preg_split('/[,\\s]+/', $raw) ?: []
        )));
    }

    /** remember-me Cookie 有效期（users.login_rememberme_cookie_days，默认 30 天） */
    public static function rememberLifetimeMinutes(): int
    {
        $days = (int) Settings::get('users.login_rememberme_cookie_days', 30);

        return max(1, $days) * 24 * 60;
    }

    protected static function on(mixed $value, bool $default = true): bool
    {
        return match ($value) {
            null, '' => $default,
            default => in_array($value, [true, 1, '1', 'true', 'on'], true),
        };
    }
}
