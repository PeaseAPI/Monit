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
            'email.required' => '请输入邮箱地址',
            'email.email' => '邮箱格式不正确',
            'password.required' => '请输入密码',
        ]);

        $remember = $request->boolean('remember');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => '邮箱或密码错误']);
        }

        if ($user->status !== 1) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => '账户已被禁用或未激活']);
        }

        Auth::login($user, $remember);

        $user->forceFill([
            'last_activity' => now(),
            'total_logins' => $user->total_logins + 1,
        ])->save();

        $this->logAccount($user, 'login');

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(Request $request)
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
        ], [
            'name.required' => '请输入用户名',
            'email.required' => '请输入邮箱地址',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '该邮箱已被注册',
            'password.required' => '请输入密码',
            'password.min' => '密码至少 8 位',
        ]);

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
        ]);

        Auth::login($user);

        $user->forceFill(['last_activity' => now(), 'total_logins' => 1])->save();
        $this->logAccount($user, 'register');

        return redirect()->route('dashboard')
            ->with('success', '欢迎来到 Monit！先创建一个网站开始统计吧。');
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
