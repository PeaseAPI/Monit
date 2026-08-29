<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        if (! \App\Support\Settings::get('affiliate.is_enabled', true)) {
            abort(404);
        }

        $user = $request->user();
        $referralKey = $user->referral_key;
        $referralUrl = route('register') . '?ref=' . $referralKey;

        $referrals = User::where('referred_by', $user->user_id)->get();

        return view('referrals.index', compact('referralKey', 'referralUrl', 'referrals'));
    }
}
