<?php

namespace App\Http\Controllers;

use App\Mail\ActivateUser;
use App\Models\User;
use App\Services\LoginLockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Monit 邮箱激活
 * 规格书 §6.1：/activate-user + /resend-activation + /sent-activation
 */
class ActivationController extends Controller
{
    /**
     * 激活用户账号
     */
    public function activate(Request $request, string $code)
    {
        $user = User::where('email_activation_code', $code)->first();

        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.activation_code_invalid')]);
        }

        if ($user->status === 1) {
            return redirect()->route('login')
                ->with('status', __('auth.already_activated'));
        }

        $user->forceFill([
            'status' => 1,
            'email_activation_code' => null,
        ])->save();

        return redirect()->route('login')
            ->with('success', __('auth.activation_success'));
    }

    /**
     * 显示重新发送激活邮件表单
     */
    public function showResendForm()
    {
        return view('auth.activation.resend');
    }

    /**
     * 重新发送激活邮件
     */
    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => __('validation.email_required'),
            'email.email' => __('validation.email_email'),
        ]);

        $identifier = strtolower(trim($validated['email']));

        // 重发激活邮件锁定（默认 3 次/30 分钟）：路由层 throttle 是 IP 维度，
        // 换 IP/分布式请求可绕过；per-email 锁定与找回密码同标准，补齐邮件轰炸面。
        // 无论邮箱是否注册都计数——不引入存在性差异。
        if (LoginLockout::blocked('activation', $identifier)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.login_locked')]);
        }
        LoginLockout::recordFailure('activation', $identifier);

        $user = User::where('email', $validated['email'])->first();

        if ($user && $user->status !== 1) {
            $code = Str::random(64);
            $user->forceFill([
                'email_activation_code' => $code,
            ])->save();

            Mail::to($user->email)->send(
                new ActivateUser($user, route('activation.activate', $code))
            );
        }

        return redirect()->route('activation.sent');
    }

    /**
     * 激活邮件已发送提示页
     */
    public function sent()
    {
        return view('auth.activation.sent');
    }
}
