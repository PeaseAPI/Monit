<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Plan;
use App\Models\User;
use App\Support\Brand;
use App\Support\Currency;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 公开前台控制器
 * 规格书 §6.1：落地页 / 博客 / 帮助 / 联系 / 定价
 */
class IndexController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        // 货币切换器（规格书 §6.1 /index + §10.4 多货币）：清单来自后台支付设置
        $currencies = Currency::all();
        $currency = strtoupper((string) $request->query('currency', ''));
        if ($currency !== '' && isset($currencies[$currency])) {
            session(['landing_currency' => $currency]);
        }
        $currency = Currency::normalize((string) session('landing_currency', ''));

        // 定价卡：优先 prices 直配价，无则按默认货币价 × 汇率换算（规格书 §10.4）
        // 定价卡：优先 prices 直配价，无则按默认货币价 × 汇率换算（规格书 §10.4）
        // 同时输出年付价（原站定价区月/年切换 + 折扣徽章）
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get()->map(function (Plan $plan) use ($currency) {
            $plan->landing_price = Currency::planPrice($plan, $currency, 'monthly');
            $plan->landing_price_annual = Currency::planPrice($plan, $currency, 'annual');
            $plan->landing_currency = $currency;

            return $plan;
        });

        // 平台统计徽章（原站 hero 下方 "9 websites / 44K pageviews"）：1 分钟缓存
        $stats = cache()->remember('landing.stats', 60, fn () => [
            'websites' => \App\Models\Website::count(),
            'pageviews' => \App\Models\SessionEvent::count() + \App\Models\LightweightEvent::count(),
        ]);

        // M23 模板机制：落地页主题由后台 branding.landing_theme 控制，
        // 视图解析 themes/{theme}/index.blade.php，不存在时回退 default 主题。
        // 二开新增主题：只需新建 resources/views/themes/{name}/index.blade.php 并在后台切换。
        $theme = Brand::landingTheme();
        $view = view()->exists("themes.{$theme}.index")
            ? "themes.{$theme}.index"
            : 'themes.default.index';

        return view($view, [
            'plans' => $plans,
            'currency' => $currency,
            'currencies' => $currencies,
            'stats' => $stats,
        ]);
    }

    public function blog(Request $request)
    {
        // 博客总开关（content.blog_is_enabled，默认开启）
        abort_unless(self::contentOn('blog_is_enabled'), 404);

        $category = $request->query('category');
        $posts = BlogPost::where('is_published', true)
            ->when($category, fn ($q) => $q->where('category_id', $category))
            ->orderByDesc('datetime')
            ->get();

        return view('blog', compact('posts'));
    }

    public function blogPost($url)
    {
        abort_unless(self::contentOn('blog_is_enabled'), 404);

        $post = BlogPost::where('is_published', true)->where('url', $url)->firstOrFail();

        return view('blog_post', compact('post'));
    }

    public function page($url)
    {
        // 自定义页面总开关（content.pages_is_enabled，默认开启）
        abort_unless(self::contentOn('pages_is_enabled'), 404);

        $page = Page::where('is_published', true)->where('url', $url)->firstOrFail();

        return view('page', compact('page'));
    }

    /**
     * 自定义页面索引（规格书 §6.1：/pages）
     */
    public function pages()
    {
        abort_unless(self::contentOn('pages_is_enabled'), 404);

        $pages = Page::where('is_published', true)->orderBy('order')->get();

        return view('pages', compact('pages'));
    }

    public function help()
    {
        return view('help');
    }

    /**
     * 联盟计划公开介绍页（规格 §6.1：/affiliate，插件启用时）
     */
    public function affiliate()
    {
        if (! Settings::get('affiliate.affiliate_is_enabled', true)) {
            abort(404);
        }

        return view('affiliate', [
            'commission' => (float) Settings::get('affiliate.affiliate_commission_percentage', 20),
            'cookieDays' => (int) Settings::get('affiliate.affiliate_cookie_duration_days', 30),
            'minWithdrawal' => (float) Settings::get('affiliate.affiliate_minimum_withdrawal_amount', 50),
        ]);
    }

    public function contact()
    {
        return view('contact');
    }

    /**
     * 联系表单提交（规格书 §6.1：/contact）
     */
    public function contactSend(Request $request)
    {
        // 人机验证（captcha.captcha_on_contact）
        if (\App\Support\Captcha::enabled('contact') && ! \App\Support\Captcha::verify(\App\Support\Captcha::tokenFrom($request->all()))) {
            return back()->withErrors(['captcha' => __('validation.captcha_failed')]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:256'],
            'message' => ['required', 'string', 'max:4096'],
        ]);

        $to = Settings::get('main.contact_email', config('mail.from.address'));

        try {
            Mail::to($to)->send(
                new ContactMessage($validated)
            );
        } catch (\Throwable $e) {
            Log::channel('stack')->info('contact-message', $validated + ['error' => $e->getMessage()]);
        }

        return back()->with('success', __('msg.contact_sent'));
    }

    /**
     * 站点地图（规格书 §6.1：/sitemap，SEO sitemap.xml）
     */
    public function sitemap()
    {
        $urls = [
            ['loc' => route('index'), 'priority' => '1.0'],
            ['loc' => route('plan'), 'priority' => '0.9'],
            ['loc' => route('blog'), 'priority' => '0.8'],
            ['loc' => route('help'), 'priority' => '0.6'],
            ['loc' => route('contact'), 'priority' => '0.6'],
        ];

        foreach (BlogPost::where('is_published', true)->orderByDesc('datetime')->limit(500)->get() as $post) {
            $urls[] = ['loc' => route('blog.post', $post->url), 'priority' => '0.7'];
        }

        foreach (Page::where('is_published', true)->limit(200)->get() as $page) {
            $urls[] = ['loc' => route('page', $page->url), 'priority' => '0.5'];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Cookie 同意记录（规格书 §6.1：/cookie-consent，GDPR）
     */
    public function cookieConsent(Request $request)
    {
        $validated = $request->validate([
            'consent' => ['required', 'in:accepted,rejected'],
        ]);

        Log::channel('stack')->info('cookie-consent', [
            'consent' => $validated['consent'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['ok' => true], 204);
    }

    
    /**
     * 邮件退订（规格书 §6.1：/unsubscribe，HMAC 签名链接）
     */
    public function unsubscribe(Request $request)
    {
        $email = (string) $request->query('email');
        $signature = (string) $request->query('sig');

        $expected = hash_hmac('sha256', $email, config('app.key'));

        if (! $email || ! hash_equals($expected, $signature)) {
            return redirect()->route('index')->with('error', __('msg.invalid_unsubscribe_link'));
        }

        $user = User::where('email', $email)->first();
        $already = $user && ! $user->is_newsletter_subscribed;

        if ($user && $user->is_newsletter_subscribed) {
            $user->update(['is_newsletter_subscribed' => false]);
        }

        return view('unsubscribe', ['email' => $email, 'already' => $already || ! $user]);
    }

    /**
     * 邮件退订 POST 处理（规格书 §6.1：/unsubscribe POST）
     */
    public function unsubscribePost(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'sig' => ['required', 'string'],
        ]);

        $expected = hash_hmac('sha256', $validated['email'], config('app.key'));

        if (! hash_equals($expected, $validated['sig'])) {
            return redirect()->route('index')->with('error', __('msg.invalid_unsubscribe_link'));
        }

        $user = User::where('email', $validated['email'])->first();
        if ($user && $user->is_newsletter_subscribed) {
            $user->update(['is_newsletter_subscribed' => false]);
        }

        return redirect()->route('index')->with('success', __('msg.unsubscribed'));
    }

    /**
     * 维护模式页面（规格书 §6.1：/maintenance）
     */
    public function maintenance()
    {
        return response()->view('maintenance', [], 503);
    }

    public function plan()
    {
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();

        return view('plan', compact('plans'));
    }

    public function apiDocs()
    {
        return view('api_docs');
    }

    /**
     * 服务条款静态页（content.terms_html）
     * 优先级：后台 content 组富文本 → 站内自定义页面（url=terms）→ 默认文案
     */
    public function terms()
    {
        return view('legal.terms', [
            'html' => (string) Settings::get('content.terms_html', ''),
        ]);
    }

    /**
     * 隐私政策静态页（content.privacy_html）
     */
    public function privacy()
    {
        return view('legal.privacy', [
            'html' => (string) Settings::get('content.privacy_html', ''),
        ]);
    }

    public function notFound()
    {
        // 404 外链（main.not_found_url）：配置时跳转指定页面（原版行为：站长可自定义 404 落点）
        if ($url = trim((string) Settings::get('main.not_found_url', ''))) {
            return redirect()->away($url, 302);
        }

        return view('errors.404', [], 404);
    }

    /**
     * content 组布尔开关（默认开启；显式 false 才关闭）
     */
    protected static function contentOn(string $key): bool
    {
        $value = Settings::get('content.'.$key);

        return $value === null || in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
