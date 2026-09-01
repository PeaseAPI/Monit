<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Sms\SmsService;
use App\Mail\ResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Monit 密码重置
 * 规格书 §6.1：/lost-password + /reset-password
 * M17 §12.5：支持手机号 + 短信验证码直接重置（/reset-password-by-sms）
 */
class ForgotPasswordController extends Controller
{
    /**
     * 显示忘记密码表单
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email', [
            'smsForgotEnabled' => SmsService::scenarioEnabled('forgot_password'),
        ]);
    }

    /**
     * 发送密码重置邮件
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        // 人机验证（captcha.captcha_on_lost_password）
        if (\App\Support\Captcha::enabled('lost_password') && ! \App\Support\Captcha::verify(\App\Support\Captcha::tokenFrom($request->all()))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['captcha' => __('validation.captcha_failed')]);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'max:256'],
        ], [
            'email.required' => __('validation.email_required'),
        ]);

        $identifier = trim($validated['email']);

        // 手机号 + 短信验证码找回（M17 §12.5）
        if (SmsService::scenarioEnabled('forgot_password') && SmsService::isPhone($identifier)) {
            $phone = SmsService::normalizePhone($identifier);

            $user = User::where('phone', $phone)->first();

            if (! $user || $user->status !== 1) {
                // 不暴露手机号是否注册
                return back()->with('status', __('auth.reset_link_sent'));
            }

            [$ok] = SmsService::send($phone, 'forgot_password');

            if (! $ok) {
                return back()->withInput()->withErrors(['email' => __('auth.sms_send_failed')]);
            }

            return redirect()
                ->route('password.reset_sms')
                ->with('phone', $phone)
                ->with('status', __('auth.sms_code_sent'));
        }

        // 邮箱流程（校验格式）
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => __('validation.email_required'),
            'email.email' => __('validation.email_email'),
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            // 不暴露邮箱是否存在，统一提示
            return back()->with('status', __('auth.reset_link_sent'));
        }

        if ($user->status !== 1) {
            return back()->with('status', __('auth.reset_link_sent'));
        }

        // 生成重置码
        $code = Str::random(64);
        $user->forceFill([
            'lost_password_code' => $code,
        ])->save();

        Mail::to($user->email)->send(
            new ResetPassword($user->email, route('password.reset', $code))
        );

        return back()->with('status', __('auth.reset_link_sent'));
    }

    /**
     * 短信重置密码表单（M17 §12.5）
     */
    public function showResetSmsForm(Request $request)
    {
        return view('auth.passwords.reset-sms', [
            'phone' => session('phone') ?? old('phone', $request->query('phone')),
        ]);
    }

    /**
     * 短信重置密码提交（M17 §12.5）
     */
    public function resetBySms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
            'sms_code' => ['required', 'digits:6'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'phone.required' => __('validation.phone_required'),
            'phone.regex' => __('validation.phone_invalid'),
            'sms_code.required' => __('validation.sms_code_required'),
            'sms_code.digits' => __('auth.sms_code_invalid'),
            'password.required' => __('validation.password_required'),
            'password.min' => __('validation.password_min'),
            'password.confirmed' => __('validation.password_confirmed'),
        ]);

        $phone = SmsService::normalizePhone($validated['phone']);

        if (! SmsService::verify($phone, 'forgot_password', $validated['sms_code'])) {
            return back()->withInput($request->except(['password', 'password_confirmation', 'sms_code']))
                ->withErrors(['sms_code' => __('auth.sms_code_invalid')]);
        }

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return back()->withErrors(['phone' => __('auth.phone_not_found')]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'lost_password_code' => null,
        ])->save();

        return redirect()->route('login')
            ->with('success', __('auth.password_reset_success'));
    }

    /**
     * 显示重置密码表单
     */
    public function showResetForm(Request $request, string $code)
    {
        $user = User::where('lost_password_code', $code)->first();

        if (! $user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => __('auth.reset_token_invalid')]);
        }

        return view('auth.passwords.reset', ['code' => $code, 'email' => $user->email]);
    }

    /**
     * 重置密码
     */
    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'email.required' => __('validation.email_required'),
            'email.email' => __('validation.email_email'),
            'password.required' => __('validation.password_required'),
            'password.min' => __('validation.password_min'),
            'password.confirmed' => __('validation.password_confirmed'),
        ]);

        $user = User::where('email', $validated['email'])
            ->where('lost_password_code', $validated['code'])
            ->first();

        if (! $user) {
            return back()->withErrors(['email' => __('auth.reset_token_invalid')]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'lost_password_code' => null,
        ])->save();

        return redirect()->route('login')
            ->with('success', __('auth.password_reset_success'));
    }
}
