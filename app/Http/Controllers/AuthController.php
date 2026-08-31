<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use App\Models\User;
use App\Services\Sms\SmsService;
use App\Services\TotpService;
use App\Services\UserAgentParser;
use App\Services\WebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // 两步验证（规格书 §12.4）：已开启则进入二步验证流程
        if ($user->twofa_is_enabled) {
            $request->session()->put([
                'twofa_user_id' => $user->user_id,
                'twofa_remember' => $remember,
                'twofa_expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route('login.twofa');
        }

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

        if (! $user || ! $user->twofa_is_enabled
            || ! TotpService::verify((string) $user->twofa_token, $validated['code'])) {
            return back()->withErrors(['code' => __('account.twofa_code_invalid')]);
        }

        $request->session()->forget(['twofa_user_id', 'twofa_remember', 'twofa_expires_at']);

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
        // 如果 URL 中有推荐码，保存到 session
        if ($ref = $request->query('ref')) {
            session(['referral_key' => $ref]);
        }

        return view('auth.register', [
            'smsRegisterEnabled' => SmsService::scenarioEnabled('register'),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
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
        ]);

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
            'language' => 'zh_CN',
            'timezone' => 'Asia/Shanghai',
            'status' => 1,
            'ip' => $request->ip(),
            'source' => 'direct',
            'referred_by' => $referredBy,
        ]);

        Auth::login($user);

        $user->forceFill(['last_activity' => now(), 'total_logins' => 1])->save();
        $this->logAccount($user, 'register');

        // 平台 Webhook：用户注册（规格 §6.3.1：webhooks.webhook_user_register_url）
        app(WebhookService::class)->userRegister([
            'user_id' => $user->user_id,
            'email' => $user->email,
            'name' => $user->name,
            'referred_by' => $referredBy,
        ]);

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
}
