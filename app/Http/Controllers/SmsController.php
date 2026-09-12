<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Sms\SmsService;
use App\Support\Typed;
use Illuminate\Http\JsonResponse;
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
    /** purpose → SmsService 场景名（scenarioEnabled 拼接 sms.sms_{name}_is_enabled） */
    /** @var array<string, string> */
    protected const SCENARIO_KEYS = [
        'register' => 'register',
        'login' => 'phone_login',
        'forgot_password' => 'forgot_password',
        'phone_bind' => 'phone_bind',
    ];

    public function send(Request $request): RedirectResponse|JsonResponse
    {
        $validated = Typed::arr($request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'purpose' => ['required', 'string', 'in:'.implode(',', SmsService::PURPOSES)],
        ], [
            'phone.required' => __('validation.phone_required'),
            'purpose.in' => __('auth.sms_not_enabled'),
        ]));

        $purpose = Typed::string($validated['purpose']);
        $phone = SmsService::normalizePhone(Typed::string($validated['phone']));

        // AJAX（弹窗绑定流程）时错误以 JSON 返回；表单流保持 redirect+errors
        $failJson = fn (string $message) => $request->expectsJson()
            ? response()->json(['message' => $message, 'errors' => ['phone' => [$message]]], 422)
            : back()->withInput()->withErrors(['phone' => $message]);

        // 场景开关
        if (! SmsService::scenarioEnabled(Typed::string(static::SCENARIO_KEYS[$purpose] ?? ''))) {
            return $failJson(__('auth.sms_not_enabled'));
        }

        // 绑定手机号需要登录态
        if ($purpose === 'phone_bind' && $request->user() === null) {
            abort(403);
        }

        // 注册：手机号不能已被占用
        if ($purpose === 'register' && User::where('phone', $phone)->exists()) {
            return $failJson(__('auth.phone_taken'));
        }

        // 登录 / 找回密码：手机号需已注册
        if (in_array($purpose, ['login', 'forgot_password'], true) && ! User::where('phone', $phone)->exists()) {
            return $failJson(__('auth.phone_not_found'));
        }

        [$ok, $error] = SmsService::send($phone, $purpose);

        if (! $ok) {
            $message = match ($error) {
                'too_frequent' => __('auth.sms_resend_too_frequent'),
                'invalid_phone' => __('validation.phone_invalid'),
                default => __('auth.sms_send_failed'),
            };

            return $failJson($message);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => __('auth.sms_code_sent')]);
        }

        return back()->with('sms_sent', true)->with('status', __('auth.sms_code_sent'));
    }
}
