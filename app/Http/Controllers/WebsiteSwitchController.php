<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 顶部网站切换器（对标 monit.cn 顶部 navbar 网站下拉）
 * 记忆当前网站到 session，供侧边栏统计入口 / 顶部选择器高亮使用
 */
class WebsiteSwitchController extends Controller
{
    public function __invoke(Request $request, Website $website): RedirectResponse
    {
        abort_unless($website->user_id === $request->user()->user_id, 403);

        session(['current_website_id' => $website->website_id]);

        return redirect()->route('dashboard', ['website_id' => $website->website_id]);
    }
}
