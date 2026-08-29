<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use App\Models\User;
use App\Services\UserAgentParser;
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
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
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
            || ! \App\Services\TotpService::verify((string) $user->twofa_token, $validated['code'])) {
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

        return view('auth.register');
    }

        public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
        ], [
                        'name.required' => __('validation.name_required'),
            'email.required' => __('validation.email_required'),
            'email.email' => __('validation.email_email'),
            'email.unique' => __('validation.email_unique'),
            'password.required' => __('validation.password_required'),
            'password.min' => __('validation.password_min'),
        ]);

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
