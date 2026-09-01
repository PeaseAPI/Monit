<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\Request;

/**
 * 用户中心 - 推荐返佣
 * 规格书 §6.2.6：Referrals
 */
class ReferralsController extends Controller
{
    public function index(Request $request)
    {
        // Affiliate 插件门控（规格书 §14.7：插件停用即关闭入口；默认开启保持向后兼容）
        // 键名与后台 affiliate 组一致；布尔以 'true'/'false' 字符串存储，须 filter_var 归一化。
        if (! filter_var(Settings::get('affiliate.affiliate_is_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            abort(404);
        }

        $user = $request->user();
        $referralKey = $user->referral_key;
        $referralUrl = route('register').'?ref='.$referralKey;

        $referrals = User::where('referred_by', $user->user_id)->get();

        return view('referrals.index', compact('referralKey', 'referralUrl', 'referrals'));
    }
}
