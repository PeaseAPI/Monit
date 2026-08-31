<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Sms\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 短信验证码发送端点（M17 规格书 §12.5）
 *
 * POST /sms/send：参数 phone + purpose（register|login|forgot_password|phone_bind）
 * 场景开关由管理后台「短信验证」设置组控制；phone_bind 需要登录态。
 */
class SmsController extends Controller
{
    /** purpose → 场景开关键 */
    protected const SCENARIO_KEYS = [
        'register' => 'sms_register_is_enabled',
        'login' => 'sms_phone_login_is_enabled',
        'forgot_password' => 'sms_forgot_password_is_enabled',
        'phone_bind' => 'sms_phone_bind_is_enabled',
    ];

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'purpose' => ['required', 'string', 'in:'.implode(',', SmsService::PURPOSES)],
        ], [
            'phone.required' => __('validation.phone_required'),
            'purpose.in' => __('auth.sms_not_enabled'),
        ]);

        $purpose = $validated['purpose'];
        $phone = SmsService::normalizePhone($validated['phone']);

        // 场景开关
        if (! SmsService::scenarioEnabled(static::SCENARIO_KEYS[$purpose])) {
            return back()->withInput()->withErrors(['phone' => __('auth.sms_not_enabled')]);
        }

        // 绑定手机号需要登录态
        if ($purpose === 'phone_bind' && ! $request->user()) {
            abort(403);
        }

        // 注册：手机号不能已被占用
        if ($purpose === 'register' && User::where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors(['phone' => __('auth.phone_taken')]);
        }

        // 登录 / 找回密码：手机号需已注册
        if (in_array($purpose, ['login', 'forgot_password'], true) && ! User::where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors(['phone' => __('auth.phone_not_found')]);
        }

        [$ok, $error] = SmsService::send($phone, $purpose);

        if (! $ok) {
            $message = match ($error) {
                'too_frequent' => __('auth.sms_resend_too_frequent'),
                'invalid_phone' => __('validation.phone_invalid'),
                default => __('auth.sms_send_failed'),
            };

            return back()->withInput()->withErrors(['phone' => $message]);
        }

        return back()->with('sms_sent', true)->with('status', __('auth.sms_code_sent'));
    }
}
