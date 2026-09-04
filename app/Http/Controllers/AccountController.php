<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Services\Sms\SmsService;
use App\Services\TotpService;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

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
        // 已启用的社交登录提供商（用户反馈 #14：账号页展示可用的第三方登录方式）
        $socialProviders = [];
        foreach (['qq', 'wechat', 'weibo', 'gitee', 'feishu', 'google', 'github', 'facebook', 'discord', 'linkedin', 'microsoft', 'apple', 'twitter'] as $provider) {
            $raw = \App\Support\Settings::get('socials.'.$provider);
            $config = is_string($raw) ? (json_decode($raw, true) ?? []) : (array) $raw;

            if (! empty($config['is_enabled'])) {
                $socialProviders[$provider] = ucfirst($provider);
            }
        }

        return view('account.index', [
            'user' => $request->user()->load('plan'),
            'socialProviders' => $socialProviders,
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
     * 更新个人资料（对标 monit.cn /account：头像 / 防钓鱼码 / 账单信息）
     * 邮箱变更时重置 email_verified_at：防止「已验证身份」随邮箱漂移到他人地址
     */
    public function update(Request $request)
    {
        $avatarMax = (int) (\App\Support\Settings::get('main.avatar_size_limit') ?: 512);

        $user = $request->user();
        $emailChanged = strtolower((string) $request->input('email')) !== strtolower((string) $user->email);

        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->user_id.',user_id'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.$avatarMax,
                // 尺寸上限：防解压炸弹（GD 渲染超大分辨率位图耗尽内存）
                'dimensions:max_width=4096,max_height=4096'],
            'avatar_remove' => ['nullable', 'boolean'],
            'anti_phishing_code' => ['nullable', 'string', 'max:64'],
            'billing_type' => ['nullable', 'in:personal,business'],
            'billing_name' => ['nullable', 'string', 'max:160'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_state' => ['nullable', 'string', 'max:120'],
            'billing_county' => ['nullable', 'string', 'max:120'],
            'billing_zip' => ['nullable', 'string', 'max:32'],
            'billing_country' => ['nullable', 'string', 'max:2'],
            'billing_phone' => ['nullable', 'string', 'max:32'],
            'billing_tax_id' => ['nullable', 'string', 'max:64'],
            'billing_notes' => ['nullable', 'string', 'max:1000'],
        ], $emailChanged ? [
            // 敏感操作确认（与其余入口一致：改密码/删账户/关 2FA 均已要求 current_password）：
            // 阻断会话窃取场景下的「改邮箱 → 忘记密码 → 重置邮件发往新邮箱」接管链
            'current_password' => ['required', 'current_password'],
        ] : []));

        // ---- 头像：上传 / 移除（存储于 public/uploads/avatars，DB 存相对 URL） ----
        $avatarUrl = $user->avatar;
        $replacingAvatar = $request->boolean('avatar_remove')
            || ($request->hasFile('avatar') && $request->file('avatar')->isValid());

        if ($replacingAvatar) {
            // 仅在确定替换/移除时清理旧文件，避免原样保存时误删
            $this->deleteLocalAvatar($user->avatar);
            $avatarUrl = null;
        }

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $file = $request->file('avatar');

            // 扩展名白名单：getClientOriginalExtension() 是客户端可控的原始值。
            // image/mimes 规则只做内容嗅探（finfo）——「GIF89a 头 + HTML」的多态
            // 文件可整体通过验证；若按原始扩展名落盘 public/uploads/avatars/
            // （Web 直达目录）→ .html/.shtml 被浏览器按 text/html 渲染即存储 XSS
            //（.php 系另有 Laravel shouldBlockPhpUpload 兜底，此处白名单为根本防线）。
            $ext = strtolower(trim($file->getClientOriginalExtension()));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                throw ValidationException::withMessages([
                    'avatar' => __('validation.image', ['attribute' => 'avatar']),
                ]);
            }

            $dir = public_path('uploads/avatars');
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            // 随机文件名：time() 同秒内重复上传会互相覆盖；随机名消除可预测性
            $filename = 'user_'.$user->user_id.'_'.Str::random(16).'.'.$ext;
            $file->move($dir, $filename);
            $avatarUrl = '/uploads/avatars/'.$filename;
        }

        // ---- 账单信息（users.billng JSON 列，仅合并提交的字段） ----
        $billing = $user->billing ?? [];
        foreach (['billing_type', 'billing_name', 'billing_address', 'billing_city', 'billing_state',
                     'billing_county', 'billing_zip', 'billing_country', 'billing_phone', 'billing_tax_id', 'billing_notes'] as $field) {
            if (array_key_exists($field, $validated)) {
                $key = substr($field, 8); // 去掉 billing_ 前缀
                $billing[$key] = $validated[$field] !== '' ? $validated[$field] : null;
            }
        }

        $update = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'billing' => $billing,
            'avatar' => $avatarUrl,
        ];

        // 防钓鱼码仅在表单提交了该字段时更新（账单表单共用此端点，避免误清）
        if (array_key_exists('anti_phishing_code', $validated)) {
            $update['anti_phishing_code'] = $validated['anti_phishing_code'] ?? null;
        }

        $user->fill(array_merge($update, $emailChanged ? ['email_verified_at' => null] : []))->save();

        return back()->with('success', __('msg.profile_updated'));
    }

    /**
     * 删除本站上传的旧头像文件（外部 URL / 社交头像不动）
     */
    private function deleteLocalAvatar(?string $avatar): void
    {
        if (! $avatar || ! str_starts_with($avatar, '/uploads/avatars/')) {
            return;
        }

        $path = public_path(ltrim($avatar, '/'));
        if (is_file($path)) {
            @unlink($path);
        }
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

        // 一次性消费：关闭 2FA 的码与登录共用判重池——防止钓鱼拿到「密码+码」后
        // 在有效窗口内重放同一码绕过双重确认
        if (! TotpService::consume((string) $user->twofa_token, $validated['code'], "user.{$user->user_id}")) {
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
