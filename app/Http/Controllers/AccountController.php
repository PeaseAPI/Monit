<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Services\Sms\SmsService;
use App\Services\TotpService;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Monit 账号设置
 * 依据规格书 §6.2.3：用户中心 - 个人资料 / 修改密码 / API Token
 */
class AccountController extends Controller
{
    /**
     * 账号设置首页
     */
    public function index(Request $request)
    {
        return view('account.index', [
            'user' => $request->user()->load('plan'),
        ]);
    }

    /**
     * 账户日志（规格书 §6.2.5：/account-logs）
     */
    public function logs(Request $request)
    {
        $logs = $request->user()->accountLogs()->orderByDesc('datetime')->paginate(25);

        return view('account.logs', compact('logs'));
    }

    /**
     * 更新个人资料
     * 邮箱变更时重置 email_verified_at：防止「已验证身份」随邮箱漂移到他人地址
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->user_id.',user_id'],
        ]);

        $emailChanged = strtolower((string) $validated['email']) !== strtolower((string) $request->user()->email);

        $request->user()->update(array_merge($validated, $emailChanged ? ['email_verified_at' => null] : []));

        return back()->with('success', __('msg.profile_updated'));
    }

    /**
     * 修改密码
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', __('msg.password_changed'));
    }

    /**
     * 生成新的 API Token
     */
    public function regenerateApiToken(Request $request)
    {
        $token = $request->user()->generateApiToken();

        return back()->with('success', __('msg.api_token_generated'))->with('api_token', $token);
    }

    /**
     * 撤销 API Token
     */
    public function revokeApiToken(Request $request)
    {
        $request->user()->update(['api_key' => null]);

        return back()->with('success', __('msg.api_token_revoked'));
    }

    /**
     * 绑定手机号（M17 §12.5）：验证码校验通过后写入 phone + phone_verified_at
     */
    public function phoneBind(Request $request)
    {
        if (! SmsService::scenarioEnabled('phone_bind')) {
            return back()->withErrors(['phone' => __('auth.sms_not_enabled')]);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/', 'unique:users,phone'],
            'sms_code' => ['required', 'digits:6'],
        ], [
            'phone.required' => __('validation.phone_required'),
            'phone.regex' => __('validation.phone_invalid'),
            'phone.unique' => __('auth.phone_taken'),
            'sms_code.required' => __('validation.sms_code_required'),
            'sms_code.digits' => __('auth.sms_code_invalid'),
        ]);

        $phone = SmsService::normalizePhone($validated['phone']);

        if (! SmsService::verify($phone, 'phone_bind', (string) $validated['sms_code'])) {
            return back()->withInput()->withErrors(['sms_code' => __('auth.sms_code_invalid')]);
        }

        $request->user()->forceFill([
            'phone' => $phone,
            'phone_verified_at' => now(),
        ])->save();

        return back()->with('success', __('account.phone_bound'));
    }

    /**
     * 两步验证（规格书 §12.4）：开始设置 —— 生成新密钥，待确认后启用
     */
    public function twofaSetup(Request $request)
    {
        $secret = TotpService::generateSecret();

        session(['twofa_pending_secret' => $secret]);

        return back()->with('twofa_setup', [
            'secret' => $secret,
            'uri' => TotpService::uri($secret, $request->user()->email),
            'qr' => TotpService::qrImageUrl(TotpService::uri($secret, $request->user()->email)),
        ]);
    }

    /**
     * 两步验证：确认启用（需输入认证器 6 位码）
     */
    public function twofaEnable(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $secret = session('twofa_pending_secret');

        if (! $secret || ! TotpService::verify($secret, $validated['code'])) {
            return back()->withErrors(['code' => __('account.twofa_code_invalid')]);
        }

        $request->user()->update([
            'twofa_token' => $secret,
            'twofa_is_enabled' => true,
        ]);

        session()->forget('twofa_pending_secret');

        return back()->with('success', __('account.twofa_enabled'));
    }

    /**
     * 两步验证：关闭（需密码 + 动态码双重确认）
     */
    public function twofaDisable(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (! TotpService::verify((string) $user->twofa_token, $validated['code'])) {
            return back()->withErrors(['code' => __('account.twofa_code_invalid')]);
        }

        $user->update([
            'twofa_token' => null,
            'twofa_is_enabled' => false,
        ]);

        return back()->with('success', __('account.twofa_disabled'));
    }

    /**
     * 删除账户表单页面（规格书 §6.2.5：/account-delete）
     */
    public function deleteForm(Request $request)
    {
        return view('account.delete', [
            'user' => $request->user(),
        ]);
    }

    /**
     * 兑换码表单页面（规格书 §6.2.5：/account-redeem-code）
     */
    public function redeemCodeForm(Request $request)
    {
        return view('account.redeem-code', [
            'user' => $request->user(),
        ]);
    }

    /**
     * 提交兑换码（规格书 §6.2.5：/account-redeem-code POST）
     */
    public function redeemCodeSubmit(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $code = Code::where('code', $validated['code'])->first();

        if (! $code) {
            return back()->withErrors(['code' => __('account.invalid_code')]);
        }

        if ($issue = $code->redemptionIssue($request->user())) {
            return back()->withErrors(['code' => __($issue)]);
        }

        // 并发窗口内计数被打满时拒绝
        if (! $code->recordRedemption($request->user())) {
            return back()->withErrors(['code' => __('account.code_fully_redeemed')]);
        }

        $code->applyToUser($request->user());

        return back()->with('success', __('account.code_redeemed_successfully'));
    }

    /**
     * 删除账户（数据导出+永久删除）
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // 先留存快照再删除（Webhook 载荷需要）
        $user = $request->user();
        $snapshot = ['user_id' => $user->user_id, 'email' => $user->email];

        $user->websites()->delete();
        $user->delete();

        // 平台 Webhook：用户删除（规格 §6.3.1：webhooks.webhook_user_delete_url）
        app(WebhookService::class)->userDelete($snapshot);

        // TODO: 发送数据导出邮件

        return redirect('/')->with('info', __('msg.account_deleted'));
    }
}
